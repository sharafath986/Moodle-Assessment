<?php

require __DIR__ . "/../../config.php";
require_once $CFG->libdir . "/enrollib.php";
global $DB, $USER, $COURSE, $PAGE, $CFG;
$PAGE->set_title("New Report");
$PAGE->set_url("/local/new_report/new_report.php");
$PAGE->navbar->add(
    "New report",
    new moodle_url("/local/new_report/new_report.php")
);
require_login();
echo $OUTPUT->header();
$PAGE->set_context(context_system::instance());

$baseurl = new moodle_url("/local/new_report/new_report.php");
$page = optional_param("page", 0, PARAM_INT);
$perpage = optional_param("perpage", 10, PARAM_INT);

$t_count = count(
    $DB->get_records_sql(
        "SELECT * FROM {user} where deleted = 0 and suspended = 0"
    )
);

$start = $page * $perpage;
if ($start > $t_count) {
    $page = 0;
    $start = 0;
}

$table = new html_table();
$table->head = [
    "S.No",
    "Contact Name",
    "Email",
    "Course Name",
    "Course Spent Hours %",
];

$table->data = [];
$table->class = "";
$table->id = "";

$i = 0;
if ($page != 0) {
    $i = $page * $perpage;
}

$datas = $DB->get_records_sql(
    "SELECT * FROM {user} where deleted = 0 and suspended = 0 and id not in (1,2)",
    [],
    $start,
    $perpage
);

$actions = "";

if (count($datas) >= 1) {
    foreach ($datas as $data) {
        // echo '<pre>';print_r($data);

        $get_user_course = enrol_get_users_courses(
            $data->id,
            $onlyactive = false,
            $fields = null,
            $sort = null
        );

        if (!empty($get_user_course)) {
            foreach ($get_user_course as $g_courses) {
                $check_logs = $DB->get_records_sql(
                    "SELECT * FROM {logstore_standard_log} where userid = $data->id  and target = 'course' and courseid = $g_courses->id",
                    [],
                    $start,
                    $perpage
                );

                if (!empty($check_logs)) {
                    $timearr = array();
                    foreach ($check_logs as $logs) {
                        $timearr[] = $logs->timecreated;
                    }

                    $c_start_time = min($timearr);
                    $c_end_time = max($timearr);

                    $difference = abs($c_end_time - $c_start_time) / 3600; //endtime - starttime * 1 hours seconds
                }

                $record_id = $data->id;
                $contact_name = $data->firstname . " " . $data->lastname;
                $email = $data->email;
                $coursename = $g_courses->fullname;
                $timespent = $difference ? substr($difference, 0, 4).  " Hrs" : " - ";

                $i++;

                $table->data[] = [
                    $i,
                    $contact_name,
                    $email,
                    $coursename,
                    $timespent,
                ];
            }
        }
    }
} else {
    $table = new html_table();
    $table->head = [
        "S.No",
        "Contact Name",
        "Email",
        "Course Name",
        "Course Spent Hours",
    ];

    $table->data = [];
    $table->class = "";
    $table->id = "";

    $table->data[] = ["", "", "No Record Found", "", ""];
}
$add_coupon = $CFG->wwwroot . "/local/new_report/new_report.php";

echo html_writer::table($table);
echo $OUTPUT->paging_bar($t_count, $page, $perpage, $baseurl);

echo $OUTPUT->footer();
