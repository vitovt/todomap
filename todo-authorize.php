<?php
 ini_set ('display_errors', 1); ini_set ('display_startup_errors', 1); error_reporting (E_ALL);

// Check if config.php exists
if (file_exists('config.php')) {
    // Include the config.php file
    include 'config.php';

    // Check if $clientId and $clientSecret are empty or not set
    if (empty($clientId) || empty($clientSecret)) {
        // Display an error message
        $errorMessage = "ClientId or clientSecret is empty. Instructions to get your API client id and secret can be found here:\nhttps://github.com/kiblee/tod0/blob/master/GET_KEY.md";
        trigger_error($errorMessage, E_USER_ERROR);
    } else {
        // Your code here with $clientId and $clientSecret
        //echo "ClientId: $clientId<br>";
        //echo "ClientSecret: $clientSecret<br>";
    }
} else {
    // Display an error message if config.php doesn't exist
    $errorMessage = "config.php does not exist. Instructions to get your API client id and secret can be found here:\nhttps://github.com/kiblee/tod0/blob/master/GET_KEY.md";
    trigger_error($errorMessage, E_USER_ERROR);
}

// Azure AD OAuth 2.0 token endpoint
$tokenEndpoint = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';

if (isset($_GET['code'])) {
    // This is the redirect from Microsoft after user login and consent.
    // Exchange the authorization code for an access token and refresh token.
    $authorizationCode = $_GET['code'];

    // Define the request data for token exchange
    $requestData = array(
        'grant_type' => 'authorization_code',
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'scope' => 'openid offline_access tasks.readwrite',
        'redirect_uri' => 'https://beast.aprt.info/todomap/todo-authorize.php', // Replace with your actual URL
        'code' => $authorizationCode,
    );

    // Send a POST request to the token endpoint to get the access token and refresh token
    $ch = curl_init($tokenEndpoint);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($requestData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Error: ' . curl_error($ch);
    } else {
        // Close the cURL session
        curl_close($ch);

        // Parse the JSON response
        $tokenData = json_decode($response, true);

        if (isset($tokenData['access_token']) && isset($tokenData['refresh_token'])) {
            // Access token and refresh token retrieved successfully
            $accessToken = $tokenData['access_token'];
            $refreshToken = $tokenData['refresh_token'];

            // You can store the access token and refresh token as needed for further use.
            //echo 'Access Token: ' . $accessToken . '<br>';
	    //echo 'Refresh Token: ' . $refreshToken . '<br>';
	    echo '<h1>Access Updated</h2>';
	    echo '<p>Tokens config updated. Now you can <a href="todomap.html">access ToDo data here</a>.</p>';
	    file_put_contents('token.cfg', $accessToken);
	    file_put_contents('refreshtoken.cfg', $refreshToken);
        } else {
            echo 'Error fetching tokens: ' . print_r($tokenData, true);
        }
    }
} else {
    // This is the initial request to initiate the authorization flow.
    // Redirect the user to the Microsoft login page for authorization.
    $authorizeUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize';
    $authorizationData = array(
        'client_id' => $clientId,
        'scope' => 'openid offline_access tasks.readwrite',
        'redirect_uri' => 'https://beast.aprt.info/todomap/todo-authorize.php', // Replace with your actual URL
        'response_type' => 'code',
    );
    $authorizeUrl = $authorizeUrl . '?' . http_build_query($authorizationData);

    header('Location: ' . $authorizeUrl);
    exit;
}
?>


