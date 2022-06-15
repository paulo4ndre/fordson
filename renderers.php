<?php

defined('MOODLE_INTERNAL') || die;

include_once($CFG->dirroot . "/mod/lesson/renderer.php");
include_once($CFG->dirroot . "/mod/lesson/locallib.php");
include_once($CFG->dirroot . "/mod/lesson/pagetypes/branchtable.php");
require_once($CFG->dirroot . "/course/renderer.php");

class theme_fordson_core_course_renderer extends core_course_renderer {
	
    public function course_section_cm_name_title(cm_info $mod, $displayoptions = array()) {

        debugging(
            'course_section_cm_name_title is deprecated. Use core_courseformat\\output\\local\\cm\\title class instead.',
            DEBUG_DEVELOPER
        );

        $output = '';
        $url = $mod->url;
        if (!$mod->is_visible_on_course_page() || !$url) {
            // Nothing to be displayed to the user.
            return $output;
        }

        //Accessibility: for files get description via icon, this is very ugly hack!
        $instancename = $mod->get_formatted_name();
        $altname = $mod->modfullname;
        // Avoid unnecessary duplication: if e.g. a forum name already
        // includes the word forum (or Forum, etc) then it is unhelpful
        // to include that in the accessible description that is added.
        if (false !== strpos(core_text::strtolower($instancename),
                core_text::strtolower($altname))) {
            $altname = '';
        }
        // File type after name, for alphabetic lists (screen reader).
        if ($altname) {
            $altname = get_accesshide(' '.$altname);
        }

        list($linkclasses, $textclasses) = $this->course_section_cm_classes($mod);

        // Get on-click attribute value if specified and decode the onclick - it
        // has already been encoded for display (puke).
        $onclick = htmlspecialchars_decode($mod->onclick, ENT_QUOTES);

        // Display link itself.
        $instancename = html_writer::tag('span', $instancename . $altname, ['class' => 'instancename ml-1']);

        $imageicon = html_writer::empty_tag('img', ['src' => $mod->get_icon_url(),
            'class' => 'activityicon', 'alt' => '', 'role' => 'presentation', 'aria-hidden' => 'true']);
        $imageicon = html_writer::tag('span', $imageicon, ['class' => 'activityiconcontainer teste courseicon']);
        $activitylink = $imageicon . $instancename;

        if ($mod->uservisible) {
            $output .= html_writer::link($url, $activitylink, array('class' => 'aalink' . $linkclasses, 'onclick' => $onclick));
        } else {
            // We may be displaying this just in order to show information
            // about visibility, without the actual link ($mod->is_visible_on_course_page()).
            $output .= html_writer::tag('div', $activitylink, array('class' => $textclasses));
        }
        return $output;
    }
	
    protected function course_name(coursecat_helper $chelper, core_course_list_element $course): string {
        $content = '';
        if ($chelper->get_show_courses() >= self::COURSECAT_SHOW_COURSES_EXPANDED) {
            $nametag = 'h3';
        } else {
            $nametag = 'div';
        }
		
        $coursename = $chelper->get_course_formatted_name($course);
        $coursenamelink = html_writer::link(new moodle_url('/course/view.php', ['id' => $course->id]),
            'CASOS <br>' . $coursename, ['class' => $course->visible ? 'case-link' : 'case-link dimmed']);
        //$content .= html_writer::tag($nametag, $coursenamelink, ['class' => 'coursename']);
		
		foreach ($course->get_course_overviewfiles() as $file) {
            $isimage = $file->is_valid_image();
            $url = moodle_url::make_file_url("$CFG->wwwroot/pluginfile.php",
                '/' . $file->get_contextid() . '/' . $file->get_component() . '/' .
                $file->get_filearea() . $file->get_filepath() . $file->get_filename(), !$isimage);
            if ($isimage) {
                $contentimages .= html_writer::empty_tag('img', ['src' => $url, 'class' => 'rounded-circle']);
            }
        }
		
		if(!$contentimages){
	$contentimages .= html_writer::empty_tag('img', ['src' => '/theme/boost/pix/course/default_profile.png', 'class' => 'rounded-circle']);
		}
		
		
		$content .= html_writer::start_tag('div', ['class' => 'course-case']);
		$content .= html_writer::tag('div', $contentimages, array('class' => 'icon-case'));
		$content .= html_writer::tag('div', $coursenamelink, array('class' => 'text-case'));
		$content .= html_writer::end_tag('div');
        
		// If we display course in collapsed form but the course has summary or course contacts, display the link to the info page.
        /*$content .= html_writer::start_tag('div', ['class' => 'moreinfo']);
        if ($chelper->get_show_courses() < self::COURSECAT_SHOW_COURSES_EXPANDED) {
            if ($course->has_summary() || $course->has_course_contacts() || $course->has_course_overviewfiles()
                || $course->has_custom_fields()) {
                $url = new moodle_url('/course/info.php', ['id' => $course->id]);
                $image = $this->output->pix_icon('i/info', $this->strings->summary);
                $content .= html_writer::link($url, $image, ['title' => $this->strings->summary]);
                // Make sure JS file to expand course content is included.
                $this->coursecat_include_js();
            }
        }
        $content .= html_writer::end_tag('div');
		*/
		
        return $content;
    }
	
	protected function coursecat_coursebox(coursecat_helper $chelper, $course, $additionalclasses = '') {
		if (!isset($this->strings->summary)) {
			$this->strings->summary = get_string('summary');
		}
		if ($chelper->get_show_courses() <= self::COURSECAT_SHOW_COURSES_COUNT) {
			return '';
		}
		if ($course instanceof stdClass) {
			$course = new core_course_list_element($course);
		}
		$content = '';
		$classes = trim('coursebox col-sm clearfix '. $additionalclasses);
		if ($chelper->get_show_courses() < self::COURSECAT_SHOW_COURSES_EXPANDED) {
			$classes .= ' collapsed';
		}

		// .coursebox
		$content .= html_writer::start_tag('div', array(
			'class' => $classes,
			'data-courseid' => $course->id,
			'data-type' => self::COURSECAT_TYPE_COURSE,
		));

		$content .= html_writer::start_tag('div', array('class' => 'info'));
		$content .= $this->course_name($chelper, $course);
		$content .= $this->course_enrolment_icons($course);
		$content .= html_writer::end_tag('div');

		$content .= html_writer::start_tag('div', array('class' => 'content'));
		$content .= $this->coursecat_coursebox_content($chelper, $course);
		$content .= html_writer::end_tag('div');

		$content .= html_writer::end_tag('div'); // .coursebox
		return $content;
	}
	
    public function frontpage_my_courses() {
        global $USER, $CFG, $DB;

        if (!isloggedin() or isguestuser()) {
            return '';
        }

        $output = '';
        $courses  = enrol_get_my_courses('summary, summaryformat');
        $rhosts   = array();
        $rcourses = array();
        if (!empty($CFG->mnet_dispatcher_mode) && $CFG->mnet_dispatcher_mode==='strict') {
            $rcourses = get_my_remotecourses($USER->id);
            $rhosts   = get_my_remotehosts();
        }

        if (!empty($courses) || !empty($rcourses) || !empty($rhosts)) {

            $chelper = new coursecat_helper();
            $totalcount = count($courses);
            if (count($courses) > $CFG->frontpagecourselimit) {
                // There are more enrolled courses than we can display, display link to 'My courses'.
                $courses = array_slice($courses, 0, $CFG->frontpagecourselimit, true);
                $chelper->set_courses_display_options(array(
                        'viewmoreurl' => new moodle_url('/my/'),
                        'viewmoretext' => new lang_string('mycourses')
                    ));
            } else if (core_course_category::top()->is_uservisible()) {
                // All enrolled courses are displayed, display link to 'All courses' if there are more courses in system.
                $chelper->set_courses_display_options(array(
                        'viewmoreurl' => new moodle_url('/course/index.php'),
                        'viewmoretext' => new lang_string('fulllistofcourses')
                    ));
                $totalcount = $DB->count_records('course') - 1;
            }
            $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_EXPANDED)->
                    set_attributes(array('class' => 'row frontpage-course-list-enrolled'));
            $output .= $this->coursecat_courses($chelper, $courses, $totalcount);

            // MNET
            if (!empty($rcourses)) {
                // at the IDP, we know of all the remote courses
                $output .= html_writer::start_tag('div', array('class' => 'courses'));
                foreach ($rcourses as $course) {
                    $output .= $this->frontpage_remote_course($course);
                }
                $output .= html_writer::end_tag('div'); // .courses
            } elseif (!empty($rhosts)) {
                // non-IDP, we know of all the remote servers, but not courses
                $output .= html_writer::start_tag('div', array('class' => 'courses'));
                foreach ($rhosts as $host) {
                    $output .= $this->frontpage_remote_host($host);
                }
                $output .= html_writer::end_tag('div'); // .courses
            }
        }
        return $output;
    }
	
    protected function coursecat_coursebox_content(coursecat_helper $chelper, $course) {
        if ($chelper->get_show_courses() < self::COURSECAT_SHOW_COURSES_EXPANDED) {
            return '';
        }
        if ($course instanceof stdClass) {
            $course = new core_course_list_element($course);
        }
        $content = $this->course_summary($chelper, $course);
        //$content .= $this->course_overview_files($course);
        $content .= $this->course_contacts($course);
        $content .= $this->course_category_name($chelper, $course);
        $content .= $this->course_custom_fields($course);
        return $content;
    }
	
}



class theme_fordson_mod_lesson_renderer extends mod_lesson_renderer {
	
}

class theme_fordson_lesson_page_type_branchtable extends lesson_page_type_branchtable {
	
}

