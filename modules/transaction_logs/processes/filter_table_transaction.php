<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";
include 'populate_filter.php';

// Decode options outside the conditional block
$decodedOptions = json_decode($jsonOptions, true);

    $college       = $_POST['college'] ?? '';
    $action       = $_POST['action'] ?? '';

    $_SESSION['filter_college'] = $college;
    $_SESSION['filter_action'] = $action;


    $query = '';
    $params = [];

switch ($action) {
    case 'activityLog':
        $query = "SELECT actLog.id, ua.user_username, ua.user_college, ua.user_level, actLog.remarks AS action, '' AS target, '' AS remarks, actLog.action_at AS created_at FROM user_auth_audit AS actLog LEFT JOIN user_account AS ua ON actLog.action_by = ua.user_id";
        break;
    case 'addStudent':
        $query = "SELECT addStd.id, ua.user_username, ua.user_college, ua.user_level, CONCAT('ADD STUDENT ON ', addStd.location) AS action, addStd.student_number AS target, addStd.remarks AS remarks, addStd.added_at AS created_at FROM student_add_audit AS addStd LEFT JOIN user_account AS ua ON addStd.added_by = ua.user_id";
        break;
    case 'updateStudent':
        $query = "SELECT updStd.id, ua.user_username, ua.user_college, ua.user_level, CONCAT('UPDATED STUDENT INFO ON ', updStd.location) AS action, updStd.student_number AS target, updStd.remarks AS remarks, updStd.updated_at AS created_at FROM student_update_audit AS updStd LEFT JOIN user_account AS ua ON updStd.updated_by = ua.user_id";
        break;
    case 'removeStudent':
        $query = "SELECT rmvStd.id, ua.user_username, ua.user_college, ua.user_level, CONCAT('REMOVE STUDENT ON ', rmvStd.location) AS action, rmvStd.student_number AS target, rmvStd.reason AS remarks, rmvStd.deleted_at AS created_at FROM student_delete_audit AS rmvStd LEFT JOIN user_account AS ua ON rmvStd.deleted_by = ua.user_id";
        break;
    case 'academicProfile':
        $query = "SELECT acdPrf.id, ua.user_username, ua.user_college, ua.user_level, CONCAT('UPDATED STUDENT ', acdPrf.location) AS action, acdPrf.student_number AS target, acdPrf.remarks AS remarks, acdPrf.updated_at AS created_at FROM student_academic_audit AS acdPrf LEFT JOIN user_account AS ua ON acdPrf.updated_by = ua.user_id";
        break;
    case 'programMetrics':
        $query = "SELECT prgMtr.id, ua.user_username, ua.user_college, ua.user_level, CONCAT('UPDATED STUDENT ', prgMtr.location) AS action, prgMtr.student_number AS target, prgMtr.remarks AS remarks, prgMtr.updated_at AS created_at FROM student_program_audit AS prgMtr LEFT JOIN user_account AS ua ON prgMtr.updated_by = ua.user_id";
        break;
    case 'reportGeneration':
        $query = "SELECT rptGnt.id, ua.user_username, ua.user_college, ua.user_level, CONCAT('GENERATED REPORT ON ', rptGnt.treatment) AS action, rptGnt.batch AS target, rptGnt.remarks AS remarks, rptGnt.generated_at AS created_at FROM user_generate_report_audit AS rptGnt LEFT JOIN user_account AS ua ON rptGnt.generated_by = ua.user_id";
        break;
    case 'additionalEntry':
        $query = "SELECT addEnt.id, ua.user_username, ua.user_college, ua.user_level, CONCAT('UPDATED ', addEnt.location) AS action, addEnt.field AS target, addEnt.remarks AS remarks, addEnt.updated_at AS created_at FROM user_additional_entry_audit AS addEnt LEFT JOIN user_account AS ua ON addEnt.updated_by = ua.user_id";
        break;
    default:
            $query = "SELECT actLog.id, ua.user_username, ua.user_college, ua.user_level, actLog.remarks AS action, '' AS target, '' AS remarks, actLog.action_at AS created_at FROM user_auth_audit AS actLog LEFT JOIN user_account AS ua ON actLog.action_by = ua.user_id";
                if (!empty($college) && $college !== 'all') {
                    $query .= " WHERE ua.user_college = ?";
                    $params[] = $college;
                }

            $query .= " UNION ALL
                SELECT addStd.id, ua.user_username, ua.user_college, ua.user_level, CONCAT('ADD STUDENT ON ', addStd.location) AS action, addStd.student_number AS target, addStd.remarks AS remarks, addStd.added_at AS created_at FROM student_add_audit AS addStd LEFT JOIN user_account AS ua ON addStd.added_by = ua.user_id
                ";
                if (!empty($college) && $college !== 'all') {
                    $query .= " WHERE ua.user_college = ?";
                    $params[] = $college;
                }

                $query .= " UNION ALL
                SELECT updStd.id, ua.user_username, ua.user_college, ua.user_level, CONCAT('UPDATED STUDENT INFO ON ', updStd.location) AS action, updStd.student_number AS target, updStd.remarks AS remarks, updStd.updated_at AS created_at FROM student_update_audit AS updStd LEFT JOIN user_account AS ua ON updStd.updated_by = ua.user_id
                ";
                if (!empty($college) && $college !== 'all') {
                    $query .= " WHERE ua.user_college = ?";
                    $params[] = $college;
                }
            
            $query .= " UNION ALL
                SELECT rmvStd.id, ua.user_username, ua.user_college, ua.user_level, CONCAT('REMOVE STUDENT ON ', rmvStd.location) AS action, rmvStd.student_number AS target, rmvStd.reason AS remarks, rmvStd.deleted_at AS created_at FROM student_delete_audit AS rmvStd LEFT JOIN user_account AS ua ON rmvStd.deleted_by = ua.user_id
                ";
                if (!empty($college) && $college !== 'all') {
                    $query .= " WHERE ua.user_college = ?";
                    $params[] = $college;
                }
            
            $query .= " UNION ALL
                SELECT acdPrf.id, ua.user_username, ua.user_college, ua.user_level, CONCAT('UPDATED STUDENT ', acdPrf.location) AS action, acdPrf.student_number AS target, acdPrf.remarks AS remarks, acdPrf.updated_at AS created_at FROM student_academic_audit AS acdPrf LEFT JOIN user_account AS ua ON acdPrf.updated_by = ua.user_id
                ";
                if (!empty($college) && $college !== 'all') {
                    $query .= " WHERE ua.user_college = ?";
                    $params[] = $college;
                }
            
            $query .= " UNION ALL
                SELECT prgMtr.id, ua.user_username, ua.user_college, ua.user_level, CONCAT('UPDATED STUDENT ', prgMtr.location) AS action, prgMtr.student_number AS target, prgMtr.remarks AS remarks, prgMtr.updated_at AS created_at FROM student_program_audit AS prgMtr LEFT JOIN user_account AS ua ON prgMtr.updated_by = ua.user_id
                ";
                if (!empty($college) && $college !== 'all') {
                    $query .= " WHERE ua.user_college = ?";
                    $params[] = $college;
                }
            
            $query .= " UNION ALL
                SELECT rptGnt.id, ua.user_username, ua.user_college, ua.user_level, CONCAT('GENERATED REPORT ON ', rptGnt.treatment) AS action, rptGnt.batch AS target, rptGnt.remarks AS remarks, rptGnt.generated_at AS created_at FROM user_generate_report_audit AS rptGnt LEFT JOIN user_account AS ua ON rptGnt.generated_by = ua.user_id
                ";
                if (!empty($college) && $college !== 'all') {
                    $query .= " WHERE ua.user_college = ?";
                    $params[] = $college;
                }
            
            $query .= " UNION ALL
                SELECT addEnt.id, ua.user_username, ua.user_college, ua.user_level, CONCAT('UPDATED ', addEnt.location) AS action, addEnt.field AS target, addEnt.remarks AS remarks, addEnt.updated_at AS created_at FROM user_additional_entry_audit AS addEnt LEFT JOIN user_account AS ua ON addEnt.updated_by = ua.user_id
                ";
        break;
}

if (!empty($college) && $college !== 'all') {
    // Admin/Dean/Assistant: apply program filter only if explicitly selected
    $query .= " WHERE ua.user_college = ?";
    $params[] = $college;
}
                
            $query .= " ORDER BY created_at DESC";

$stmt = $con->prepare($query);
$stmt->execute($params);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $college_name = '';
    foreach ($decodedOptions['collegeOptions'] as $collegeOption) {
        if ($collegeOption['id'] == $row['user_college']) {
            $college_name = $collegeOption['name'];
            break;
        }
    }

    // Map year level ID to name
    $role = '';
    if ($row['user_level'] == 0) {
        $role = "Admin";
    } elseif ($row['user_level'] == 1) {
        $role = "Dean";
    } elseif ($row['user_level'] == 2) {
        $role = "Administrative Assistant";
    } elseif ($row['user_level'] == 3) {
        $role = "Program Head";
    }

    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['user_username']) . "</td>";
    echo "<td>" . htmlspecialchars($college_name) . "</td>";
    echo "<td>" . htmlspecialchars($role) . "</td>";
    echo "<td>" . htmlspecialchars($row['action']) . "</td>";
    echo "<td>" . htmlspecialchars($row['target']) . "</td>";
    echo "<td>" . htmlspecialchars($row['remarks']) . "</td>";
    echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
    echo "</tr>";
}
//echo $query;
?>