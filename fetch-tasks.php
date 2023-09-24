<?php

        $accessToken = "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX";

// Define the Microsoft To Do API endpoint URLs
$todoListsUrl = 'https://graph.microsoft.com/v1.0/me/todo/lists';
$tasksUrl = 'https://graph.microsoft.com/v1.0/me/todo/lists/{listId}/tasks';

// Define your Microsoft Graph API access token retrieval logic here
// Example: $accessToken = fetchAccessToken();

// Check if the access token is available and not empty
if (!empty($accessToken)) {
    try {
        // Fetch task lists from Microsoft To Do API
        $response = fetchTasks($accessToken, $todoListsUrl);
        
        // Return the JSON response
        header('Content-Type: application/json');
        echo json_encode($response);
    } catch (Exception $error) {
        // Handle any errors here
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => $error->getMessage()]);
    }
} else {
    // Handle the case where access token is not available
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Unauthorized']);
}

function fetchTasks($accessToken, $todoListsUrl) {
    global $tasksUrl;
    try {
        // Perform the actual API request to fetch task lists from Microsoft To Do API
        $listsResponse = performApiRequest($todoListsUrl, $accessToken);

        // Check if the request was successful
        if ($listsResponse['status'] !== 200) {
            throw new Exception("Error fetching task lists: " . $listsResponse['status']);
        }

        // Parse the JSON response
        $todoListsData = json_decode($listsResponse['body'], true);

        // Initialize the mindmapData array
        $mindmapData = [
            'name' => 'ToDoTasks',
            'children' => []
        ];

        // Fetch tasks for each task list
        foreach ($todoListsData['value'] as $list) {
            // Construct the tasks URL by replacing {listId} with the actual list ID
            $listTasksUrl = str_replace('{listId}', $list['id'], $tasksUrl);

            // Perform the API request to fetch tasks for the current list
            $tasksResponse = performApiRequest($listTasksUrl, $accessToken);

            // Check if the request was successful
            if ($tasksResponse['status'] !== 200) {
                throw new Exception("Error fetching tasks for list {$list['displayName']}: " . $tasksResponse['status']);
            }

            // Parse the JSON response
            $tasksData = json_decode($tasksResponse['body'], true);

            // Initialize arrays to separate tasks into 'ToDo' and 'Done' categories
            $toDoTasks = [];
            $doneTasks = [];

            foreach ($tasksData['value'] as $task) {
                // Create a task node
                $taskNode = [
                    'name' => $task['title'],
                    'description' => 'Description',
                    'url' => '',
                    'free' => true
                    // Add other task properties as needed
                ];

                if ($task['status'] === 'completed') {
                    $doneTasks[] = $taskNode;
                } else {
                    $toDoTasks[] = $taskNode;
                }
            }

            // Create 'ToDo' and 'Done' nodes under the task list
            $listNode = [
                'name' => $list['displayName'],
                'description' => $list['wellknownListName'],
                'children' => [
                    [
                        'name' => 'ToDo',
                        'description' => 'Tasks to be done',
                        'children' => $toDoTasks
                    ],
                    [
                        'name' => 'Done',
                        'description' => 'Completed tasks',
                        'children' => $doneTasks
                    ]
                ]
            ];

            // Add the list node to the mindmapData
            $mindmapData['children'][] = $listNode;
        }

        return $mindmapData;
    } catch (Exception $error) {
        // Handle any errors here
        throw new Exception("Error fetching tasks: " . $error->getMessage());
    }
}

function performApiRequest($url, $accessToken) {
    // Initialize cURL session
    $ch = curl_init($url);

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);

    // Execute the cURL request and get the response
    $response = curl_exec($ch);

    // Get the HTTP status code
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Close cURL session
    curl_close($ch);

    return [
        'status' => $httpStatus,
        'body' => $response
    ];
}

?>
