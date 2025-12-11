<?php
function getProfilePictureHTML($patient_data, $relative_path = '../') {
    $profile_picture = $patient_data['profile_picture'] ?? null;
    $patient_name = $patient_data['pname'] ?? 'User';
    
    if (!empty($profile_picture) && file_exists($relative_path . $profile_picture)) {
        return '<img src="' . $relative_path . htmlspecialchars($profile_picture) . '" alt="Profile" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">';
    } else {
        return strtoupper(substr($patient_name, 0, 2));
    }
}
?>