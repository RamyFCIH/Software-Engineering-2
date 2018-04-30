

<!DOCTYPE html>
<html>
<head>
<title>Title of the document</title>
<link rel="stylesheet" href="Custome.css">
</head>

<body>
<button class="btn btn-warning">osama</button>

<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * My Moodle -- a user's personal dashboard
 *
 * - each user can currently have their own page (cloned from system and then customised)
 * - only the user can see their own dashboard
 * - users can add any blocks they want
 * - the administrators can define a default site dashboard for users who have
 *   not created their own dashboard
 *
 * This script implements the user's view of the dashboard, and allows editing
 * of the dashboard.
 *
 * @package    moodlecore
 * @subpackage my
 * @copyright  2010 Remote-Learner.net
 * @author     Hubert Chathi <hubert@remote-learner.net>
 * @author     Olav Jordan <olav.jordan@remote-learner.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../config.php');
require_once($CFG->dirroot . '/my/lib.php');

redirect_if_major_upgrade_required();

// TODO Add sesskey check to edit
$edit   = optional_param('edit', null, PARAM_BOOL);    // Turn editing on and off
$reset  = optional_param('reset', null, PARAM_BOOL);

require_login();

$hassiteconfig = has_capability('moodle/site:config', context_system::instance());
if ($hassiteconfig && moodle_needs_upgrading()) {
    redirect(new moodle_url('/admin/index.php'));
}

$strmymoodle = get_string('myhome');

if (isguestuser()) {  // Force them to see system default, no editing allowed
    // If guests are not allowed my moodle, send them to front page.
    if (empty($CFG->allowguestmymoodle)) {
        redirect(new moodle_url('/', array('redirect' => 0)));
    }

    $userid = null;
    $USER->editing = $edit = 0;  // Just in case
    $context = context_system::instance();
    $PAGE->set_blocks_editing_capability('moodle/my:configsyspages');  // unlikely :)
    $header = "$SITE->shortname: $strmymoodle (GUEST)";
    $pagetitle = $header;

} else {        // We are trying to view or edit our own My Moodle page
    $userid = $USER->id;  // Owner of the page
    $context = context_user::instance($USER->id);
    $PAGE->set_blocks_editing_capability('moodle/my:manageblocks');
    $header = fullname($USER);
    $pagetitle = $strmymoodle;
}

// Get the My Moodle page info.  Should always return something unless the database is broken.
if (!$currentpage = my_get_page($userid, MY_PAGE_PRIVATE)) {
    print_error('mymoodlesetup');
}

// Start setting up the page
$params = array();
$PAGE->set_context($context);
$PAGE->set_url('/my/index.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');
$PAGE->set_subpage($currentpage->id);
$PAGE->set_title($pagetitle);
$PAGE->set_heading($header);

if (!isguestuser()) {   // Skip default home page for guests
    if (get_home_page() != HOMEPAGE_MY) {
        if (optional_param('setdefaulthome', false, PARAM_BOOL)) {
            set_user_preference('user_home_page_preference', HOMEPAGE_MY);
        } else if (!empty($CFG->defaulthomepage) && $CFG->defaulthomepage == HOMEPAGE_USER) {
            $frontpagenode = $PAGE->settingsnav->add(get_string('frontpagesettings'), null, navigation_node::TYPE_SETTING, null);
            $frontpagenode->force_open();
            $frontpagenode->add(get_string('makethismyhome'), new moodle_url('/my/', array('setdefaulthome' => true)),
                    navigation_node::TYPE_SETTING);
        }
    }
}

// Toggle the editing state and switches
if (empty($CFG->forcedefaultmymoodle) && $PAGE->user_allowed_editing()) {
    if ($reset !== null) {
        if (!is_null($userid)) {
            require_sesskey();
            if (!$currentpage = my_reset_page($userid, MY_PAGE_PRIVATE)) {
                print_error('reseterror', 'my');
            }
            redirect(new moodle_url('/my'));
        }
    } else if ($edit !== null) {             // Editing state was specified
        $USER->editing = $edit;       // Change editing state
    } else {                          // Editing state is in session
        if ($currentpage->userid) {   // It's a page we can edit, so load from session
            if (!empty($USER->editing)) {
                $edit = 1;
            } else {
                $edit = 0;
            }
        } else {
            // For the page to display properly with the user context header the page blocks need to
            // be copied over to the user context.
            if (!$currentpage = my_copy_page($USER->id, MY_PAGE_PRIVATE)) {
                print_error('mymoodlesetup');
            }
            $context = context_user::instance($USER->id);
            $PAGE->set_context($context);
            $PAGE->set_subpage($currentpage->id);
            // It's a system page and they are not allowed to edit system pages
            $USER->editing = $edit = 0;          // Disable editing completely, just to be safe
        }
    }

    // Add button for editing page
    $params = array('edit' => !$edit);

    $resetbutton = '';
    $resetstring = get_string('resetpage', 'my');
    $reseturl = new moodle_url("$CFG->wwwroot/my/index.php", array('edit' => 1, 'reset' => 1));

    if (!$currentpage->userid) {
        // viewing a system page -- let the user customise it
        $editstring = get_string('updatemymoodleon');
        $params['edit'] = 1;
    } else if (empty($edit)) {
        $editstring = get_string('updatemymoodleon');
    } else {
        $editstring = get_string('updatemymoodleoff');
        $resetbutton = $OUTPUT->single_button($reseturl, $resetstring);
    }

    $url = new moodle_url("$CFG->wwwroot/my/index.php", $params);
    $button = $OUTPUT->single_button($url, $editstring);
    $PAGE->set_button($resetbutton . $button);

} else {
    $USER->editing = $edit = 0;
}

echo $OUTPUT->header();

echo $OUTPUT->custom_block_region('content');

echo $OUTPUT->footer();

// Trigger dashboard has been viewed event.
$eventparams = array('context' => $context);
$event = \core\event\dashboard_viewed::create($eventparams);
$event->trigger();
    ?>
<div class="margin-auto center">
<button type="button" class="btn btn-warning btn-lg" data-toggle="modal" data-target="#myModal">
  User Helper
</button>
</div>    


<!-- Modal -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">Welcome To User Helper</h4>
      </div>
        <!--model Body -->
      <div class="modal-body">
         <div class="header-h1">
             <h4>General Guide Lines for Users :</h4>
         </div><!--header-h1-->
          <div class="navigation-help">
              <ol class="navigation">
                  <li>You should register a new account during moodle setup</li>
                  <li>You should login to your account based on your username and password of your account,like the figure below </li>
                  <li>If you do not have an account ,you can login as a guest user.</li>
              </ol><!--navigation-->
          </div><!--navigation-help-->
          <div class="teacher-help">
             <h4 style="font-size:18px"> Teacher Guide Lines :</h4>
              <div class="navigation-help margin">
                   <ol>
                       <li><p class="base-li">Adding New Course</p>
                         <ul>
                             <li>By default a regular teacher can't add a new course
                                 . To add a new course to Moodle, you need to have either Administrator,
                                 Course Creator or Manager rights.
                             </li> 
                             <li><p class="h20" style="font-weight:bold">To Add New Course: </p>
                               <ul>
                                 <li>
                                     <p>From the Site administration link, click Courses>Manage courses and categories</p>
                                   <img src="add-course.PNG" class="add-course-img" style="margin:5px;" />
                                     </li>
                                   <li>Click on the category where you want your course to be. For more information see Course categories</li>
                                   <li>Click the "New course" link</li>
                                   <li>
                                       <p>Enter the course settings, and then choose either to "Save and return" to go back to your course, or "Save and display" to go to the next screen.
                                       </p>
                                    <img src="save-and-display.PNG" />
                                   </li>
                                   <li>On the next screen,if you have chosen "Save and display",
                                       choose your students/teachers to assign to the course.
                                   </li>
                                 </ul>
                             </li>
                             
                             
                         </ul>
                       </li>
                       <li><p class="base-li">Creating a basic scheduler</p>
                         <ul>
                           <li><p>Click the "Turn editing on" button.</p>
                             <img src="turn-edition-on.PNG" />  
                           </li>
                             <li>Click the link "Add an activity or resource."</li>
                             <li><p>In the box that appears, choose "Scheduler" and click the "Add" button.</p>
                                 <img src="scheduler.png" />
                             </li>
                             <li>A web page will appear called "Adding a new Scheduler." Type in the name of the scheduler, which can be changed later.
                             </li>
                             <li>Scroll to the bottom of the webpage and click the "Save and display" button.</li>
                             <li><p>A webpage will appear with the name of your scheduler at the top. Click the "Add slots" link.</p>
                                  <img src="Add-slot.PNG" />  
                             </li>
                             <li>A drop-down menu will appear that allows you to add repeated slots or a single slot. Click the link to add repeated slots.
                             </li>
                             <li>
                                 <p>Options will appear to add time slots. For this lesson, the slots you add can be real or fictitious, for practice. All time slots must occur in the future, and the start time must be before the end time. The simplest option is to:
                                  
                                 </p>
                                  <ol>
                                     <li>Click the "Start time" menu and choose a time that is one hour from now.</li>
                                       <li>Click the "End time" menu and choose a time that is one hour after the start time.</li>
                                   </ol>
                             
                             </li>
                             <li>Scroll to the bottom of the webpage and click "Save changes."</li>
                         </ul>
                       </li>
                       
                       <li><p>Scheduler: My Appointments</p>
                           <br>
                           <p>This screen allows the teacher to manage his or her own appointments independently from any other teacher in the course. Basically, the features are similar to the All Appointments screen, but the slot list has been filtered so only your appointments and free slots are visible.
                           </p>
                           <ul>
                             <li>Add And Delete Commands
                                 <p>At top of the screen is an Add And Delete control bar that allows calling forms for adding a single slot or a series of slots. When the scheduler is empty, the bar looks like this:

                                 </p>   
                                 <img src="scheduler-inner-1.PNG" class="img-responsive" />
                                 <p>When slots have been entered, the bar adds more "Delete" features:</p>
                                 <img src="scheduler-inner-2.PNG" class="img-responsive" />
                                 <p>This bar may have more or less Delete features, depending on whether you can manage appointments for colleagues or not.

                                 </p>
                            </li>
                           </ul>
                       </li>
                       <li><p>How To fill the Slots</p>
                         <p>When first added to a course, the scheduler will not have any slots set up. Each enrolled teacher can add their own slots if they have the "mod/scheduler:attend" capability.
                         Clicking on "Add slots" allows you to add either one slot or repeated slots. You will be able to add slots using the form below:
                           </p>
                           <img src="fill-slots.png" class="img-responsive" />
                       </li>
                       
                       <li><p style="font-size:16px">Adding slots from Elements</p>
                       
                           <ul>
                             <li>Start date for making slots.</li>
                               <li>End date for making slots. Keeping it equal to start date will make slots for a single day. Over these dates, slots will be repeated based on the day of week and time.
                               </li>
                               <li>You can choose which days of the week to create slots on. Take care to not disable all of the days in the date range you selected.
                               </li>
                               <li>The hour range for making slots can be set here. The hour range can roll over 00:00.</li>
                               <li>Selecting "Divide into slots" will create multiple slots fitting within the range specified above. If not selected, a single slot with the given duration will be created at start of the hour range.
                               </li>
                               <li>Set the duration for each slot.</li>
                               <li>You may set a break to appear after each slot, allowing you time to write notes or rest.</li>
                               <li>The "Force when overlap" setting will allow the slot creating procedure to continue even if there are some other slots on the way. This will also remove the old slots after the new ones are created.</li>
                               <li>Using "Maximum number of students per slot" you can specify how many students should be able to sign up for each slot. For one-on-one meetings, this would be 1 student.
                               </li>
                               <li>Here, you can set the location where you will meet students.</li>
                               <li>If you own the "mod/scheduler:manageallappointments" or the "mod/scheduler:canscheduletootherteachers" capability, you may appoint for someone else than yourself.
                               </li>
                               <li>You may change these setting to limit availability of slots to students based on how far away the slots are. Options include "Now" (always available), "1-6 days before", and "1-6 weeks before."
                               </li>
                               <li>You may control when a reminder will be sent by mail to the student. Options include "Now" (always available), "1-6 days before", and "1-6 weeks before."
                               </li>
                           </ul>
                       </li>
                       
                  </ol>
                  
              </div><!--navigation-help-->
          </div><!--teacher-help-->
          <div class="student-help">
            <h4 style="font-size:18px;margin-top:45px"> Student Guide Lines :</h4>
              <ol>
                <li><p style="font-size:16px;">Scheduler : Appointing</p>
                     <p>Students can only choose or remove an appointment. They only will be able to select published slots. If no slots are available, a "wait" screen is displayed:</p> 
                    <img src="student-1.PNG" class="img-responsive" />
                    <p>If there are some slots available, the student will be asked to choose a slot in a list:</p>
                    <img src="student-2.PNG" />
                    <ol>
                      <li style="margin-top: 28px;margin-bottom: 20px;">Using the radio buttons, a student will be able to choose a slot.</li>
                        <li>If the student is in one or more groups in the course, he or she will be able to schedule an appointment for one of his or her groups. The student may choose a group to schedule, and doing so will schedule all other members of the group in that slot. The student will not be allowed to schedule another group.
                        </li>
                    </ol>
                </li>
              </ol>
          </div><!--student-help-->
          
      </div>
        <!--Modal Footer-->
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>
  
</body>
</html>
