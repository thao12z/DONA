<?php
/**
 * Spam Protection & DDoS Prevention
 *
 * Rules:
 * - Track actions per IP + user_id
 * - Window: 5 minutes (300 seconds)
 * - Threshold: 20 actions
 * - Penalty: Ban 10 minutes (600 seconds)
 * - Recurrence: 3 violations in 3 days = Permanent ban
 */

class SpamProtection {
    private $db;
    private $userId;
    private $ipAddress;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->userId = $_SESSION['user_id'] ?? null;
        $this->ipAddress = getClientIP();
    }

    /**
     * Check if user/IP is currently banned
     */
    public function isBanned() {
        $now = date('Y-m-d H:i:s');

        $permanentBan = $this->db->fetchOne(
            "SELECT id FROM spam_tracking
             WHERE (user_id = ? OR ip_address = ?)
             AND permanent_ban = 1",
            [$this->userId, $this->ipAddress]
        );

        if ($permanentBan) {
            return [
                'banned' => true,
                'permanent' => true,
                'message' => 'Tài khoản của bạn đã bị khóa vĩnh viễn do vi phạm quy định nhiều lần.'
            ];
        }

        $temporaryBan = $this->db->fetchOne(
            "SELECT ban_until FROM spam_tracking
             WHERE (user_id = ? OR ip_address = ?)
             AND ban_until IS NOT NULL
             AND ban_until > ?
             ORDER BY ban_until DESC
             LIMIT 1",
            [$this->userId, $this->ipAddress, $now]
        );

        if ($temporaryBan) {
            $banUntil = strtotime($temporaryBan['ban_until']);
            $remainingMinutes = ceil(($banUntil - time()) / 60);

            return [
                'banned' => true,
                'permanent' => false,
                'ban_until' => $temporaryBan['ban_until'],
                'message' => "Bạn đã bị tạm khóa do thực hiện quá nhiều hành động. Vui lòng thử lại sau {$remainingMinutes} phút."
            ];
        }

        return ['banned' => false];
    }

    /**
     * Track action and check for spam
     */
    public function trackAction($actionType = 'general') {
        $banStatus = $this->isBanned();
        if ($banStatus['banned']) {
            return $banStatus;
        }

        $windowStart = date('Y-m-d H:i:s', strtotime('-' . SPAM_WINDOW_SECONDS . ' seconds'));

        $recentActions = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM spam_tracking
             WHERE (user_id = ? OR ip_address = ?)
             AND action_type = ?
             AND window_start > ?",
            [$this->userId, $this->ipAddress, $actionType, $windowStart]
        );

        if ($recentActions >= SPAM_MAX_ACTIONS) {
            return $this->applyBan($actionType);
        }

        $this->logAction($actionType);

        return ['banned' => false, 'actions_count' => $recentActions + 1];
    }

    /**
     * Apply ban (temporary or permanent)
     */
    private function applyBan($actionType) {
        $threeDaysAgo = date('Y-m-d H:i:s', strtotime('-3 days'));

        $recentViolations = $this->db->fetchColumn(
            "SELECT COUNT(DISTINCT DATE(window_start)) as violation_days
             FROM spam_tracking
             WHERE (user_id = ? OR ip_address = ?)
             AND window_start > ?
             GROUP BY user_id, ip_address",
            [$this->userId, $this->ipAddress, $threeDaysAgo]
        );

        $isPermanent = $recentViolations >= SPAM_PERMANENT_BAN_THRESHOLD;
        $banUntil = $isPermanent ? null : date('Y-m-d H:i:s', time() + SPAM_BAN_DURATION);

        $this->db->insert('spam_tracking', [
            'user_id' => $this->userId,
            'ip_address' => $this->ipAddress,
            'action_type' => $actionType,
            'action_count' => SPAM_MAX_ACTIONS,
            'ban_until' => $banUntil,
            'permanent_ban' => $isPermanent ? 1 : 0
        ]);

        if ($this->userId) {
            $this->db->update('users',
                [
                    'is_banned' => 1,
                    'ban_until' => $banUntil
                ],
                'id = ?',
                [$this->userId]
            );
        }

        logActivity($this->userId ?? 0, 'SPAM_BAN', sprintf(
            'User/IP banned. Type: %s, Permanent: %s, IP: %s',
            $actionType,
            $isPermanent ? 'Yes' : 'No',
            $this->ipAddress
        ));

        if ($isPermanent) {
            return [
                'banned' => true,
                'permanent' => true,
                'message' => 'Tài khoản của bạn đã bị khóa vĩnh viễn do vi phạm quy định nhiều lần.'
            ];
        }

        $banMinutes = SPAM_BAN_DURATION / 60;
        return [
            'banned' => true,
            'permanent' => false,
            'ban_until' => $banUntil,
            'message' => "Bạn đã bị tạm khóa {$banMinutes} phút do thực hiện quá nhiều hành động trong thời gian ngắn."
        ];
    }

    /**
     * Log action
     */
    private function logAction($actionType) {
        $this->db->insert('spam_tracking', [
            'user_id' => $this->userId,
            'ip_address' => $this->ipAddress,
            'action_type' => $actionType,
            'action_count' => 1
        ]);
    }

    /**
     * Clean old records (call this periodically via cron)
     */
    public static function cleanup() {
        $db = Database::getInstance();
        $threeDaysAgo = date('Y-m-d H:i:s', strtotime('-3 days'));

        $db->delete('spam_tracking',
            'permanent_ban = 0 AND window_start < ?',
            [$threeDaysAgo]
        );

        $oneDayAgo = date('Y-m-d H:i:s', strtotime('-1 day'));
        $db->query(
            "UPDATE users SET is_banned = 0, ban_until = NULL
             WHERE is_banned = 1 AND ban_until < ?",
            [$oneDayAgo]
        );
    }

    /**
     * Check rate limit (lighter than trackAction, for read operations)
     */
    public function checkRateLimit($maxRequests = 100, $windowSeconds = 60) {
        $windowStart = date('Y-m-d H:i:s', strtotime("-{$windowSeconds} seconds"));

        $recentRequests = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM spam_tracking
             WHERE ip_address = ?
             AND window_start > ?",
            [$this->ipAddress, $windowStart]
        );

        if ($recentRequests >= $maxRequests) {
            return [
                'allowed' => false,
                'message' => 'Quá nhiều yêu cầu. Vui lòng thử lại sau.'
            ];
        }

        return ['allowed' => true, 'remaining' => $maxRequests - $recentRequests];
    }
}

/**
 * Helper function
 */
function spamProtection() {
    static $sp = null;
    if ($sp === null) {
        $sp = new SpamProtection();
    }
    return $sp;
}

/**
 * Middleware to protect routes
 */
function requireSpamCheck($actionType = 'general') {
    $sp = spamProtection();
    $result = $sp->trackAction($actionType);

    if ($result['banned']) {
        if (isAjaxRequest()) {
            errorResponse($result['message'], 429);
        } else {
            die('<h1>Tài khoản bị khóa</h1><p>' . $result['message'] . '</p>');
        }
    }
}

function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}
