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

namespace mod_bigbluebuttonbn\output;

use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\external\get_recordings_to_import;
use mod_bigbluebuttonbn\local\config;
use mod_bigbluebuttonbn\local\helpers\roles;
use renderable;
use renderer_base;
use stdClass;
use templatable;
use moodle_exception;

/**
 * Renderable for the import page.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class import implements renderable, templatable {
    /** @var instance $destinationinstance */
    protected instance $destinationinstance;

    /** @var int|null $sourceinstanceid The source instance ID or null if not set */
    protected ?int $sourceinstanceid;

    /** @var int|null $sourcecourseid The source course ID or null if not set */
    protected ?int $sourcecourseid;

    /** @var string $originpage The origin page */
    protected string $originpage;

    /** @var array $originparams The origin parameters */
    protected array $originparams;

    /**
     * import constructor.
     *
     * @param instance $destinationinstance
     * @param int $sourcecourseid
     * @param int $sourceinstanceid
     * @param string $originpage
     * @param array $originparams
     */
    public function __construct(
        instance $destinationinstance,
        int $sourcecourseid,
        int $sourceinstanceid,
        string $originpage = 'view',
        array $originparams = []
    ) {
        $this->destinationinstance = $destinationinstance;
        $this->sourcecourseid = $sourcecourseid >= 0 ? $sourcecourseid : null;
        $this->sourceinstanceid = $sourceinstanceid >= 0 ? (int) $sourceinstanceid : 0;
        $this->originpage = $originpage;
        $this->originparams = $originparams;
    }

    /**
     * Export data for Mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $templatedata = $this->initialize_template_data();

        // Build the base action URL for select controls.
        $actionurl = $this->build_action_url();
        $templatedata->recordings->session->sourcecourseid = $this->sourcecourseid ?? 0;

        // Now the selects.
        if ($this->sourcecourseid) {
            $templatedata->bbb_select = $this->build_source_instance_selector($output, $actionurl);
        }

        // Course selector.
        $templatedata->course_select = $this->build_course_selector($output, $actionurl);

        if ($this->sourcecourseid !== null) {
            $templatedata->has_selected_course = true;
        }

        // Attempt to load and render recordings for import.
        if (!empty($this->sourcecourseid) && !empty($this->sourceinstanceid)) {
            $templatedata = $this->add_recordings_to_template($templatedata);
        }

        // Back button.
        $templatedata->back_button = $this->build_back_button($output);

        return $templatedata;
    }

    /**
     * Initializes base template structure with default values.
     *
     * @return stdClass
     */
    protected function initialize_template_data(): stdClass {
        $courses = roles::import_get_courses_for_select($this->destinationinstance);
        if (config::get('importrecordings_from_deleted_enabled')) {
            $courses[0] = get_string('recordings_from_deleted_activities', 'mod_bigbluebuttonbn');
            ksort($courses);
        }

        $templatedata = (object) [
            'instanceid' => $this->destinationinstance->get_instance_id(),
            'recordings' => (object) [
                'session' => (object) [
                    'bbbid' => $this->destinationinstance->get_instance_id(),
                    'has_recordings' => true,
                ],
                'output' => (object) [],
            ],
        ];

        // If a source instance is selected, update session info.
        if (!empty($this->sourceinstanceid)) {
            $templatedata->recordings->session->sourceinstanceid = $this->sourceinstanceid;
            $templatedata->recordings->session->searchbutton = ['value' => ''];

            $sourceinstance = instance::get_from_instanceid($this->sourceinstanceid);
            if ($sourceinstance->is_type_room_only()) {
                $templatedata->recordings->session->has_recordings = false;
            }
        }

        return $templatedata;
    }

    /**
     * Builds the base action URL for the page.
     *
     * @return \moodle_url
     */
    protected function build_action_url(): \moodle_url {
        return $this->destinationinstance->get_page_url('import', [
            'destbn' => $this->destinationinstance->get_instance_id(),
            'originpage' => $this->originpage,
            'originparams' => http_build_query($this->originparams),
        ]);
    }

    /**
     * Builds the select element for picking a source BBB instance.
     *
     * @param renderer_base $output
     * @param \moodle_url $actionurl
     * @return stdClass|null
     */
    protected function build_source_instance_selector(renderer_base $output, \moodle_url $actionurl): ?stdClass {
        $selectrecords = [];
        $cms = get_fast_modinfo($this->sourcecourseid)->instances['bigbluebuttonbn'] ?? [];

        foreach ($cms as $cm) {
            // Skip the target instance and any that are being deleted.
            if ($cm->id == $this->destinationinstance->get_cm_id() || $cm->deletioninprogress) {
                continue;
            }
            $selectrecords[$cm->instance] = $cm->name;
        }

        if (config::get('importrecordings_from_deleted_enabled')) {
            $selectrecords[0] = get_string('recordings_from_deleted_activities', 'mod_bigbluebuttonbn');
        }

        $actionurl->param('sourcecourseid', $this->sourcecourseid);

        return (new \single_select(
            $actionurl,
            'sourcebn',
            $selectrecords,
            $this->sourceinstanceid ?? ""
        ))->export_for_template($output);
    }

    /**
     * Builds the course selector dropdown.
     *
     * @param renderer_base $output
     * @param \moodle_url $actionurl
     * @return stdClass
     */
    protected function build_course_selector(renderer_base $output, \moodle_url $actionurl): stdClass {
        $courses = roles::import_get_courses_for_select($this->destinationinstance);

        if (config::get('importrecordings_from_deleted_enabled')) {
            $courses[0] = get_string('recordings_from_deleted_activities', 'mod_bigbluebuttonbn');
            ksort($courses);
        }

        return (new \single_select(
            $actionurl,
            'sourcecourseid',
            $courses,
            $this->sourcecourseid ?? ""
        ))->export_for_template($output);
    }

    /**
     * Loads recordings via external call and adds them to the template.
     *
     * @param stdClass $templatedata
     * @return stdClass
     */
    protected function add_recordings_to_template(stdClass $templatedata): stdClass {
        try {
            $recordings = get_recordings_to_import::execute(
                $this->destinationinstance->get_instance_id(),
                $this->sourceinstanceid,
                $this->sourcecourseid,
                'import',
                null
            );

            if (!empty($recordings['tabledata']['data'])) {
                $recordingsoutput = json_decode($recordings['tabledata']['data'], true);

                if (!empty($recordingsoutput)) {
                    $recordingsoutput[0]['first'] = true;
                }

                foreach ($recordingsoutput as &$recording) {
                    if (!empty($recording['date'])) {
                        $recording['date'] = userdate($recording['date'] / 1000, '%B %d, %Y, %I:%M %p');
                    }
                }

                $templatedata->recordings->output = $recordingsoutput;
            }

            if (!empty($recordings['warnings'])) {
                debugging('Warnings while fetching recordings: ' . json_encode($recordings['warnings']));
            }
        } catch (moodle_exception $e) {
            debugging('Error fetching recordings: ' . $e->getMessage());
        }

        return $templatedata;
    }

    /**
     * Builds the back button to return to the origin page.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    protected function build_back_button(renderer_base $output): stdClass {
        $destinationurl = $this->destinationinstance->get_page_url($this->originpage, $this->originparams);

        return (new \single_button(
            $destinationurl,
            get_string('view_recording_button_return', 'mod_bigbluebuttonbn')
        ))->export_for_template($output);
    }
}
