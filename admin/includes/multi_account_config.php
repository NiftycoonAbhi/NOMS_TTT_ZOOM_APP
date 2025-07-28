<?php
// multi_account_config.php - Multi-Account Zoom Configuration Manager
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../db/dbconn.php';

/**
 * Get all active Zoom API credentials from database
 * @return array Array of zoom credentials
 */
function getAllZoomCredentials() {
    global $conn;
    
    $query = "SELECT * FROM zoom_api_credentials WHERE is_active = 1 ORDER BY name ASC";
    $result = $conn->query($query);
    
    $credentials = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $credentials[] = $row;
        }
    }
    
    return $credentials;
}

/**
 * Get specific Zoom credentials by ID
 * @param int $credentials_id
 * @return array|null Zoom credentials or null if not found
 */
function getZoomCredentialsById($credentials_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM zoom_api_credentials WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $credentials_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Set current Zoom account in session
 * @param int $credentials_id
 * @return bool Success status
 */
function setCurrentZoomAccount($credentials_id) {
    $credentials = getZoomCredentialsById($credentials_id);
    
    if ($credentials) {
        $_SESSION['current_zoom_account'] = $credentials;
        $_SESSION['zoom_credentials_id'] = $credentials_id;
        return true;
    }
    
    return false;
}

/**
 * Get current Zoom account from session
 * @return array|null Current zoom credentials or null
 */
function getCurrentZoomAccount() {
    return isset($_SESSION['current_zoom_account']) ? $_SESSION['current_zoom_account'] : null;
}

/**
 * Get current Zoom credentials ID from session
 * @return int|null Current zoom credentials ID or null
 */
function getCurrentZoomCredentialsId() {
    return isset($_SESSION['zoom_credentials_id']) ? $_SESSION['zoom_credentials_id'] : null;
}

/**
 * Check if user has selected a Zoom account
 * @return bool True if account selected
 */
function hasSelectedZoomAccount() {
    return isset($_SESSION['current_zoom_account']) && !empty($_SESSION['current_zoom_account']);
}

/**
 * Clear current Zoom account from session
 */
function clearCurrentZoomAccount() {
    unset($_SESSION['current_zoom_account']);
    unset($_SESSION['zoom_credentials_id']);
}

/**
 * Logout user and redirect to account selection
 */
function logoutUser($redirect_url = 'select_zoom_account.php') {
    // Clear all session data
    session_unset();
    session_destroy();
    
    // Redirect to account selection page
    header("Location: $redirect_url");
    exit();
}

/**
 * Get Zoom credentials for API calls
 * @return array|null Array with account_id, client_id, client_secret or null
 */
function getZoomApiCredentials() {
    $current = getCurrentZoomAccount();
    
    if ($current) {
        return [
            'account_id' => $current['account_id'],
            'client_id' => $current['client_id'],
            'client_secret' => $current['client_secret'],
            'name' => $current['name']
        ];
    }
    
    return null;
}

/**
 * Redirect to account selection if no account selected
 * @param string $redirect_url Where to redirect for account selection
 */
function requireZoomAccountSelection($redirect_url = 'select_zoom_account.php') {
    if (!hasSelectedZoomAccount()) {
        header("Location: $redirect_url");
        exit();
    }
}
?>
