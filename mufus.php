<?php
/*
 *
 *
 */
// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

class DFP_JSON_USERS {

    // Email of User who can Create Users
    private $adminemail = 'easydocTest@proman.at';
    // pwd: test
    // Medmedia is Blog Nr. 1
    private $blogid = '1';

    // Required Fields for creating a User
    private $required_data = array(
        'firstname',
        'surname',
        'password',
        'birthday',
        'email',
        'country',
        'salutation',
        'professional'
    );
    // Optional Fields for creating a User
    private $additional_data = array(
        'country',
        'salutation',
        'professional',
        'title',
        'professionalCode',
        'professionalAddon',
        'doctorNumber',
        'newsletters',
        'clientVersion',
        'clientName',
        'plz'
    );
    // Available xProfile Fields
    private $xprofile_fields = array(
        'birthday' => 'Geb. Datum',
        'title' => 'Titel',
        'country' => 'Land',
        'salutation' => 'Anrede',
        'professional' => 'Berufsgruppe',
        'professionalCode' => 'Fachgruppe',
        'professionalAddon' => 'Zusatzgebiet',
        'doctorNumber' => 'Arztnummer',
        'easy_firstname' => 'Vorname',
        'easy_surname' => 'Nachname',
        'plz'       => 'PLZ'
    );
    private $knews_fields = array(
        'newsletters' => 'newsletter_id'
    );

    // Roles
    private $customroles = array(
        'Arzt' => 'arzt',
        'Apotheker' => 'apotheker',
        'Medizinstudent' => 'medizinstudent',
        'Industrie' => 'industrie',
        'Medizinische Berufe' => 'medizinische_berufe',
        'Medizinjournalist' => 'medizinjournalist',
        'PKA' => 'pka',
        'Andere' => 'andere',
        'Pflegeberufe' => 'pflegeberufe'
    );

    /**
     * Register the user-related routes
     *
     * @param array $routes Existing routes
     * @return array Modified routes
     */
    public function register_routes($routes) {
        $user_routes = array(
            // User endpoints
            '/v1/user' => array(
                array(
                    array(
                        $this,
                        'get_current_user'
                    ),
                    WP_JSON_Server::READABLE | WP_JSON_Server::HIDDEN_ENDPOINT
                ),
                array(
                    array(
                        $this,
                        'create_user'
                    ),
                    WP_JSON_Server::EDITABLE | WP_JSON_Server::ACCEPT_JSON | WP_JSON_Server::HIDDEN_ENDPOINT
                ),
            ),

            '/v1/updateuser' => array(
                array(
                    array(
                        $this,
                        'update_user'
                    ),
                    WP_JSON_Server::EDITABLE | WP_JSON_Server::ACCEPT_JSON | WP_JSON_Server::HIDDEN_ENDPOINT
                ),
            ),

            '/v1/user/(?P<id>\d+)' => array(
                array(
                    array(
                        $this,
                        'get_user'
                    ),
                    WP_JSON_Server::READABLE | WP_JSON_Server::HIDDEN_ENDPOINT
                ),
                array(
                    array(
                        $this,
                        'delete_user'
                    ),
                    WP_JSON_Server::DELETABLE | WP_JSON_Server::HIDDEN_ENDPOINT
                ),
            ),

            '/v1/setarztnummer' => array(
                array(
                    array(
                        $this,
                        'set_arztnummer'
                    ),
                    WP_JSON_Server::EDITABLE | WP_JSON_Server::ACCEPT_JSON | WP_JSON_Server::HIDDEN_ENDPOINT
                ),
            ),

            '/v1/setpassword' => array(
                array(
                    array(
                        $this,
                        'set_password'
                    ),
                    WP_JSON_Server::READABLE | WP_JSON_Server::HIDDEN_ENDPOINT
                ),
            ),

            '/v1/lastfall' => array(
                array(
                    array(
                        $this,
                        'get_last_fall'
                    ),
                    WP_JSON_Server::READABLE | WP_JSON_Server::HIDDEN_ENDPOINT
                ),
                array(
                    array(
                        $this,
                        'cu_last_fall'
                    ),
                    WP_JSON_Server::EDITABLE | WP_JSON_Server::HIDDEN_ENDPOINT
                ),
            ),

            '/v1/getcheckemail/(?P<email>.+)' => array(
                array(
                    array(
                        $this,
                        'check_email_wp'
                    ),
                    WP_JSON_Server::READABLE | WP_JSON_Server::HIDDEN_ENDPOINT
                ),
            ),

            '/v1/checkemail/(?P<email>.+)' => array(
                array(
                    array(
                        $this,
                        'check_email_address'
                    ),
                    WP_JSON_Server::READABLE | WP_JSON_Server::HIDDEN_ENDPOINT
                ),
            ),

            '/v1/subscribenews/(?P<newsId>\d+)' => array(
                array(
                    array(
                        $this,
                        'subscribe_news'
                    ),
                    WP_JSON_Server::READABLE | WP_JSON_Server::HIDDEN_ENDPOINT
                )
            ),

            '/v1/unsubscribenews/(?P<newsId>\d+)' => array(
                array(
                    array(
                        $this,
                        'unsubscribe_news'
                    ),
                    WP_JSON_Server::READABLE | WP_JSON_Server::HIDDEN_ENDPOINT
                )
            )
        );
        return array_merge($routes, $user_routes);
    } //

    /**
     * @param $id           The user id
     * @param $email        The email address of the user
     * @param $password     The new password
     */
    public function set_password($id, $email, $password) {
        global $wpdb;

        // Check if user id and email match
        if ($email == "")
            return "no email";

        $user = get_user_by('email', $email);
        if ($user->ID != $id)
            return "no match";

        // Set the password
        $lPassword = wp_hash_password($password); //  md5( $password );
        $wpdb->update($wpdb->users, array('user_pass' => $lPassword), array('ID' => $id));
        wp_cache_delete($id, 'users');

        return "done: " . $lPassword;
    }


    /**
     * Set or update Arztnummer for a user
     *
     * @param string $id
     * @return response
     */
    public function set_arztnummer($id, $arztnummer) {
        $current_user_id = get_current_user_id();
        if (empty($current_user_id)) {
            error_log('REPORT: set_arztnummer 1');
            return new WP_Error('json_not_logged_in', __('Sie sind derzeit nicht eingeloggt.'), array('status' => 403,));
        }
        if ($current_user_id != $id) {
            error_log('REPORT: set_arztnummer 2');
            return new WP_Error('json_not_currentuser', __('Sie können nur Ihre eigene Arztnummer editieren.'), array('status' => 403,));
        }
        if (empty($arztnummer)) {
            error_log('REPORT: set_arztnummer 3');
            return new WP_Error('json_no_data', __('Bitte geben Sie eine Arztnummer ein.'), array('status' => 403,));
        }

        //$oReturn = new stdClass();
        $easydoc_UserClass = new easydoc_Users;
        $_setarztnummer = $easydoc_UserClass->set_arztnummer($id, $arztnummer);
        return $_setarztnummer;
    } // set_arztnummer


    /**
     * Subscribe to a specfic newsletter by its id
     *
     * @param string $newsId The id of the newsletter to be subscribed by the user
     * @return boolean        True, if successful or false if not
     */
    public function subscribe_news($newsId) {
        // Check if user is logged in
        $current_user_id = get_current_user_id();
        if (empty($current_user_id)) {
            error_log('REPORT: subscribe_news');
            return new WP_Error('json_not_logged_in', __('You are not currently logged in.'), array('status' => 403,));
        }

        // Get the email address from the logged in user
        $current_user = wp_get_current_user();
        $lEmailAddress = $current_user->user_email;

        // Handle newsletters
        global $wpdb;
        global $Knews_plugin;

        if (!$Knews_plugin->initialized)
            $Knews_plugin->init();

        // Get the custom fields to be used in knews from bb
        $extra_fields = $Knews_plugin->get_extra_fields();

        $custom_fields = array();
        $nachname_bp = bp_get_member_profile_data('field=Nachname');
        $vorname_bp = bp_get_member_profile_data('field=Vorname');
        $title_bp = bp_get_member_profile_data('field=Titel');
        $anrede_bp = bp_get_member_profile_data('field=Anrede');

        foreach ($extra_fields as $field) {
            if ($field->name == 'surname')
                $custom_fields[$field->id] = $nachname_bp;
            if ($field->name == 'name')
                $custom_fields[$field->id] = $vorname_bp;
            if ($field->name == 'titel')
                $custom_fields[$field->id] = $title_bp;
            if ($field->name == 'anrede')
                $custom_fields[$field->id] = $anrede_bp;
        }

        // Write the custom fields for the user
        $user_id = get_user_id($lEmailAddress);
        while ($cf = current($custom_fields)) {
            $Knews_plugin->set_user_field($user_id, key($custom_fields), esc_sql($cf), true);
            next($custom_fields);
        }

        // And add the user to knews
        $Knews_plugin->add_user($lEmailAddress, $newsId, 'de', 'de_DE', $custom_fields, true);

        // Return true
        return true;
    }


    /**
     * Subscribe to a specfic newsletter by its id
     *
     * @param string $newsId The id of the newsletter to be subscribed by the user
     * @return boolean          True, if successful or false if not
     */
    public function unsubscribe_news($newsId) {
        // Check if user is logged in
        $current_user_id = get_current_user_id();
        if (empty($current_user_id)) {
            error_log('REPORT: unsubscribe_news');
            return new WP_Error('json_not_logged_in', __('You are not currently logged in.'), array('status' => 403,));
        }

        // Get the email address from the logged in user
        $current_user = wp_get_current_user();
        $lEmailAddress = $current_user->user_email;

        // Unsubscribe the user from the mailing list
        delete_user_from_mailinglist($lEmailAddress, $newsId);

        // Return true
        return true;
    }


    /**
     *  Check if an given email-address is registered in wp
     *
     * @param string $email The email-address which should be checked
     * @return boolean          True, if the email-address is free or false if not
     */

    public function check_email_wp($email) {

        $exists = email_exists($email);
        $oReturn = new stdClass();
        $oReturn->email = $email;

        if ($exists) {

            $oReturn->status = true;
        } else {
            $oReturn->status = false;
        }

        return $oReturn;
    } // check_email_wp


    /**
     *  Check if an given email-address is free or not
     *
     * @param string $email The email-address which should be checked
     * @return boolean          True, if the email-address is free or false if not
     */

    public function check_email_address($email) {

        // Check if user is logged in
        $current_user_id = get_current_user_id();
        if (empty($current_user_id)) {
            error_log('REPORT: check_email_address');
            return new WP_Error('json_not_logged_in', __('You are not currently logged in.'), array('status' => 403,));
        }

        // Check if email is the same as the user's one
        $current_user = wp_get_current_user();
        if ($current_user->user_email == $email) {
            // It is the same email ==> email is ok
            return true;
        }

        // Try to get the user with this email-address
        $lUser = get_user_by('email', $email);
        if ($lUser === false) {
            // No user with this email found ==> email is free
            return true;
        }

        // Email is not free
        return false;
    }


    /**
     * Get Last Visited Fall Data for a User
     *
     * @param string $id
     * @return response
     */
    public function get_last_fall($id) {
        $current_user_id = get_current_user_id();
        if (empty($current_user_id)) {
            error_log('REPORT: get_last_fall');
            return new WP_Error('json_not_logged_in', __('Sie sind derzeit nicht eingeloggt.'), array('status' => 403,));
        }

        $oReturn = new stdClass();
        $easydoc_lastfalldata = new easydoc_Users;
        $oReturn = $easydoc_lastfalldata->get_visitedfall($current_user_id, $id);
        return $oReturn;
    } // get_last_fall


    /**
     * Create or Update Last Visited Fall Data for a User
     *
     * @param string $id
     * @param string $fallid
     * @return response
     */
    public function cu_last_fall($id, $fallid) {
        $current_user_id = get_current_user_id();
        if (empty($current_user_id)) {
            error_log('REPORT: cu_last_fall');
            return new WP_Error('json_not_logged_in', __('Sie sind derzeit nicht eingeloggt.'), array('status' => 403,));
        }

        $oReturn = new stdClass();
        $easydoc_lastfalldata = new easydoc_Users;
        $oReturn = $easydoc_lastfalldata->write_visitedfall($current_user_id, $id, $fallid);
        return $oReturn;
    } // cu_last_fall


    /**
     * Get Current Logged in User Profile
     *
     * @return response
     */
    public function get_current_user() {

        $current_user_id = get_current_user_id();
        if (empty($current_user_id)) {
            // error_log('REPORT: get_current_user');
            return new WP_Error('json_not_logged_in', __('You are not currently logged in.'), array('status' => 403,));
        }
        $response = json_ensure_response($this->get_user($current_user_id));

        if (is_wp_error($response)) {
            return $response;
        }

        if (!($response instanceof WP_JSON_ResponseInterface)) {
            $response = new WP_JSON_Response($response);
        }

        $response->header('Access-Control-Allow-Origin', "*");
        $response->header('Access-Control-Allow-Headers', "Authorization, Accept, Origin, Content-Type");
        $response->header('Access-Control-Allow-Methods', "GET, POST, PUT, DELETE, OPTIONS");

        return $response;
    }

    /**
     * Retrieve a user.
     *
     * @param int $id User ID
     * @return response
     */
    public function get_user($id) {
        $current_user_id = get_current_user_id();

        if ($current_user_id !== $id && !current_user_can('list_users')) {
            error_log('REPORT: get_user');
            return new WP_Error('json_user_cannot_list', __('Sorry, you are not allowed to view this user.'), array('status' => 403));
        }

        $user = get_userdata($id);

        if (empty($user->ID)) {
            return new WP_Error('json_user_invalid_id', __('Invalid user ID.'), array('status' => 400));
        }

        return $this->prepare_user($user);
    }

    /**
     *
     * Prepare a User entity from a WP_User instance.
     *
     * @param $user : WP_User Object
     * @param $context : point of origin, default is for web, dpo app; apoon is for APO:ON APP
     * @return array
     */
    public function prepare_user($user, $context = 'default') {
        if (!function_exists('bp_is_active')) {
            error_log('REPORT: prepare_user 1');
            return new WP_Error('prepare_user', __('Buddypress is not active'), array('status' => 403));
        }

        $user_fields = array(
            'ID' => $user->ID,
            'firstname' => $user->first_name,
            'surname' => $user->last_name,
            'eMail' => $user->user_email,
            'Role' => $user->roles
        );

        // Buddypress xProfile Fields
        if (!bp_has_profile(array('user_id' => $user->ID))) {
            error_log('REPORT: prepare_user 2');
            return new WP_Error('json_post_invalid_id', __('Invalid User.'), array('status' => 403));
        }

        foreach ($this->xprofile_fields as $user_fieldname => $bp_xprofilefieldname) {
            if ($user_fieldname == 'birthday') {
                $field_id = xprofile_get_field_id_from_name($this->xprofile_fields['birthday']);
                $birthday = maybe_unserialize(BP_XProfile_ProfileData::get_value_byid($field_id, $user->ID));
                $datetime = date("Y-m-d\TH:i:sP", strtotime($birthday));
                $user_fields[$user_fieldname] = $datetime;
            } elseif ($user_fieldname == 'professional' || $user_fieldname == 'professionalCode' || $user_fieldname == 'professionalAddon') {
                $user_fields[$user_fieldname] = (array)xprofile_get_field_data($bp_xprofilefieldname, $user->ID);
            } else {
                $user_fields[$user_fieldname] = xprofile_get_field_data($bp_xprofilefieldname, $user->ID);
            }
        }

        // Knews Subscribed mailinglists
        // $user_fields['newsletters'] = $this->get_newsletters($user->ID);

        switch ($context) {

            // Default Behaviour for Web
            case 'web':

                // Get Microlearning Abos
                $easydoc_MicroClass = new easydoc_Micro;
                $_ml_abos = $easydoc_MicroClass->get_ml_abos_2($user->ID, 'default');  //! 0.2 sec
                if ($_ml_abos) {
                    $user_fields['microlearning'] = $_ml_abos->abos;
                }

                // Courses
                $easydoc_UserClass = new easydoc_Users;
                $courses_data = $easydoc_UserClass->get_visited_courses($user->ID);

                if (!is_wp_error($courses_data) || !empty($courses_data)) {

                    $easydoc_courses = new easydoc_Courses;
                    //$_cf_fieldnames = $easydoc_courses->get_cf_names();

                    $easydocdata = array();
                    $i = 0;


                    // Get all visited "fall"
                    global $wpdb;
                    $lLastVisitedFalls = $wpdb->get_results("
							SELECT *
							FROM $wpdb->easydoclastvisitedfall
							WHERE UID = $user->ID
						");

                    $lGetSubmits = $wpdb->get_results("
							SELECT *
							FROM $wpdb->easydocdata
							WHERE UID = $user->ID
						");

                    foreach ($courses_data as $course_id) {
                        // Get Course Data
                        // arg, was get_course
                        $tmp_data = $easydoc_courses->get_course_desktop($course_id, 'teaser');

                        // Get Url
                        $courses_visitedData = new stdClass();
                        $courses_visitedData->visitedfall = false;
                        foreach ($lLastVisitedFalls as $lLastFall) {
                            if ($lLastFall->courseid == $course_id) {
                                $courses_visitedData->visitedfall['fallid'] = $lLastFall->fallid;
                                break;
                            }
                        }

                        if (!is_wp_error($tmp_data) || !empty($tmp_data)) {
                            $easydocdata[$i] = $tmp_data;

                            if ($courses_visitedData->visitedfall) {
                                // Generate permalink
                                $easydocdata[$i]->lastvisitedfallurl = $easydoc_courses->get_easydoc_permalink($course_id, $tmp_data->medienTyp, $courses_visitedData->visitedfall['fallid']);
                                $easydocdata[$i]->lastvisitedcaseid = $courses_visitedData->visitedfall['fallid'];
                            } else {
                                $easydocdata[$i]->lastvisitedfallurl = $easydoc_courses->get_easydoc_permalink($course_id, $tmp_data->medienTyp);
                                $easydocdata[$i]->lastvisitedcaseid = -1;
                            }
                        }

                        $_currentCourseProgress = $easydoc_UserClass->get_progress( $user->ID, $course_id );
                        $easydocdata[$i]->courseprogress = $_currentCourseProgress->percent;
//                        $easydocdata[$i]->courseprogress = 100;

                        $_coursestatus = array();
                        foreach ($lGetSubmits as $lGetSubmit) {
                            if ($lGetSubmit->courseid == $course_id) {
                                $_coursestatus = $lGetSubmit;
                                break;
                            }
                        }

                        if (!empty($_coursestatus->status)) {
                            $easydocdata[$i]->coursestatus = $_coursestatus->status->status;
                        }

                        $i++;
                    } // endforeach

                    //$user_fields['courses_id'] = $courses_data;
                    if (!empty($easydocdata)) {
                        $user_fields['courses'] = $easydocdata;
                    }
                }

                break;

            // Default Behaviour for  DPO
            case 'default':

                // Get Microlearning Abos
                $easydoc_MicroClass = new easydoc_Micro;
                $_ml_abos = $easydoc_MicroClass->get_ml_abos_2($user->ID, 'default');
                if ($_ml_abos) {
                    $user_fields['microlearning'] = $_ml_abos->abos;
                }

                // Courses
                $easydoc_UserClass = new easydoc_Users;
                $courses_data = $easydoc_UserClass->get_visited_courses($user->ID);

                if (!is_wp_error($courses_data) || !empty($courses_data)) {

                    $easydoc_courses = new easydoc_Courses;
                    $_cf_fieldnames = $easydoc_courses->get_cf_names();

                    $easydocdata = array();
                    $i = 0;

                    foreach ($courses_data as $course_id) {

                        // Get Course Data
                        // arg, was get_course
                        $tmp_data = $easydoc_courses->get_course_desktop($course_id, 'teaser');

                        // Only Include Courses with CF Field: easydoc_apo_apptarget  set to apoon
                        if ($tmp_data->apptarget == $_cf_fieldnames['apptarget_apoon']) {
                            continue;
                        }

                        // Get Url
                        $courses_visitedData = $easydoc_UserClass->get_visitedfall($user->ID, $course_id);
                        if (!is_wp_error($tmp_data) || !empty($tmp_data)) {
                            $easydocdata[$i] = $tmp_data;

                            if ($courses_visitedData->visitedfall) {
                                // Generate permalink
                                $easydocdata[$i]->lastvisitedfallurl = $easydoc_courses->get_easydoc_permalink($course_id, $tmp_data->medienTyp, $courses_visitedData->visitedfall['fallid']);
                                $easydocdata[$i]->lastvisitedcaseid = $courses_visitedData->visitedfall['fallid'];
                            } else {
                                $easydocdata[$i]->lastvisitedfallurl = $easydoc_courses->get_easydoc_permalink($course_id, $tmp_data->medienTyp);
                                $easydocdata[$i]->lastvisitedcaseid = -1;
                            }
                        }

                        $_currentCourseProgress = $easydoc_UserClass->get_progress($user->ID, $course_id);
                        $easydocdata[$i]->courseprogress = $_currentCourseProgress->percent;

                        $_coursestatus = $easydoc_UserClass->get_easydoc_submit($user->ID, $course_id);
                        if (!empty($_coursestatus->status)) {
                            $easydocdata[$i]->coursestatus = $_coursestatus->status->status;
                        }

                        $i++;
                    } // endforeach

                    //$user_fields['courses_id'] = $courses_data;
                    if (!empty($easydocdata)) {
                        $user_fields['courses'] = $easydocdata;
                    }

                }

                break;

            // Behaviour for APO:ON
            case 'apoon':

                // Get Microlearning Abos
                $easydoc_MicroClass = new easydoc_Micro;
                $_ml_abos = $easydoc_MicroClass->get_ml_abos_2($user->ID, 'apoon');
                if ($_ml_abos) {
                    $user_fields['microlearning'] = $_ml_abos->abos;
                }

                // Courses
                $easydoc_UserClass = new easydoc_Users;
                $courses_data = $easydoc_UserClass->get_visited_courses($user->ID);

                if (!is_wp_error($courses_data) || !empty($courses_data)) {

                    $easydoc_courses = new easydoc_Courses;
                    $_cf_fieldnames = $easydoc_courses->get_cf_names();

                    $easydocdata = array();
                    $i = 0;

                    foreach ($courses_data as $course_id) {

                        // Get Course Data
                        $tmp_data = $easydoc_courses->get_course_desktop($course_id, 'teaser');

                        // Only Include Courses with CF Field: easydoc_apo_apptarget  set to apoon
                        if (!$tmp_data->apptarget || ($tmp_data->apptarget != $_cf_fieldnames['apptarget_apoon'])) {
                            continue;
                        }

                        // Get Url
                        $courses_visitedData = $easydoc_UserClass->get_visitedfall($user->ID, $course_id);
                        if (!is_wp_error($tmp_data) || !empty($tmp_data)) {
                            $easydocdata[$i] = $tmp_data;

                            if ($courses_visitedData->visitedfall) {
                                // Generate permalink
                                $easydocdata[$i]->lastvisitedfallurl = $easydoc_courses->get_easydoc_permalink($course_id, $tmp_data->medienTyp, $courses_visitedData->visitedfall['fallid']);
                                $easydocdata[$i]->lastvisitedcaseid = $courses_visitedData->visitedfall['fallid'];
                            } else {
                                $easydocdata[$i]->lastvisitedfallurl = $easydoc_courses->get_easydoc_permalink($course_id, $tmp_data->medienTyp);
                                $easydocdata[$i]->lastvisitedcaseid = -1;
                            }
                        }

                        $_currentCourseProgress = $easydoc_UserClass->get_progress($user->ID, $course_id);
                        $easydocdata[$i]->courseprogress = $_currentCourseProgress->percent;

                        $_coursestatus = $easydoc_UserClass->get_easydoc_submit($user->ID, $course_id);
                        if (!empty($_coursestatus->status)) {
                            $easydocdata[$i]->coursestatus = $_coursestatus->status->status;
                        }

                        $i++;
                    } // endforeach

                    //$user_fields['courses_id'] = $courses_data;
                    if (!empty($easydocdata)) {
                        $user_fields['courses'] = $easydocdata;
                    }

                }

                break;
        }

        return apply_filters('json_prepare_user', $user_fields, $user);
    }


    /**
     * Get All Mailinglists and generate List for a User ID
     *
     * @param int $user_id
     * @return array*
     */
    private function get_newsletters($user_id) {
        global $Knews_plugin;
        $oReturn = array();

        if (!isset($Knews_plugin)) {
            return new WP_Error('get_newsletters', __('Knews not installed'), array('status' => 404));
        }
        if (empty($user_id)) {
            error_log('REPORT: get_newsletters');
            return new WP_Error('get_newsletters', __('No User found.'), array('status' => 403));
        }

        if (!$Knews_plugin->initialized)
            $Knews_plugin->init();

        $array_knewslist = $Knews_plugin->tellMeLists();

        $user_info = get_userdata($user_id);
        $user_email = $user_info->user_email;
        $user_newslist = user_subscribed_list($user_email);
        if (!is_array($user_newslist)) {
            $user_newslist = (array)$user_newslist;
        }

        foreach ($array_knewslist as $kID => $kName) {
            if (in_array($kID, $user_newslist)) {
                $oReturn[] = array(
                    'title' => $kName,
                    'subscription' => true,
                    'id' => $kID
                );
            } else {
                $oReturn[] = array(
                    'title' => $kName,
                    'subscription' => false,
                    'id' => $kID
                );
            }
        } // endforeach

        return $oReturn;
    }


    /**
     * Create a new user.
     *
     * @param $data
     * @return mixed
     */
    public function create_user($data, $_source = 'App') {

        foreach ($this->required_data as $arg) {
            if (empty($data[$arg])) {
                return new WP_Error('json_missing_callback_param', sprintf(__('Missing parameter %s'), $arg), array('status' => 400));
            }
        }

        // Only User easydoctest@proman.org can create users
        $current_user = wp_get_current_user();
        if ($current_user->user_email != $this->adminemail) {
            //if ( ! current_user_can( 'create_users' ) ) {
            error_log('REPORT: create_user() 1 : ' . $current_user->user_email);
            return new WP_Error('json_cannot_create2', __('Sorry, you are not allowed to create users.'), array('status' => 403));
        }

        if (!empty($data['ID'])) {
            return new WP_Error('json_user_exists', __('User (email already registered.)'), array('status' => 400));
        }

        if (!empty($data['email'])) {
            $exists = email_exists($data['email']);
            if ($exists) {
                return new WP_Error('json_user_exists', __('Für diese Email existiert bereits ein Konto.'), array('status' => 400));
                error_log('REPORT: create_user() - email already exists:' . $data['email']);
            }
        }

        $user_id = $this->insert_user($data);

        if (is_wp_error($user_id)) {
            error_log('REPORT: create_user() - error: ' . $user_id);
            return $user_id;
        }


        // Save User Registration into DB
        global $wpdb;
        // Make Timestmap
        $current_time = current_time('mysql');

        //$_asource = "App";

        if (!isset($wpdb->count_registrations)) {
            $_data_table = $wpdb->prefix . "count_registrations";
            $wpdb->count_registrations = $_data_table;
            $wpdb->tables[] = str_replace($wpdb->prefix, '', $_data_table);
        }

        $wpdb_insertdata = $wpdb->insert($wpdb->count_registrations, array(
                'UID' => $user_id,
                'source' => $_source,
                'time' => $current_time
            ));


        // Get ID of Created User
        // Cannot do that, because easydoc is not allowed to view a user
        if ($current_user->id != $user_id && !current_user_can('list_users')) {
            $oReturn[] = array('ID' => $user_id);
            return $oReturn;
        }

        $response = $this->get_user($user_id);
        if (!$response instanceof WP_JSON_ResponseInterface) {
            $response = new WP_JSON_Response($response);
        }
        $response->set_status(200);
        $response->header('Location', json_url('/users/' . $user_id));

        //error_log('REPORT: create_user() - finished: ' . $user_id );

        return $response;
    }


    /**
     * Updates a User.
     *
     * @param
     * @return
     */
    public function update_user($data) {
        $user = new stdClass;
        $oReturn = new stdClass;
        global $wpdb;

        if (empty($data['ID'])) {
            return new WP_Error('json_no_id', __('Fehlende BenutzerID.'), array('status' => 404));
        } else {
            $existing = get_userdata($data['ID']);

            if (!$existing) {
                return new WP_Error('json_user_invalid_id', __('Ungültige Benutzer ID.'), array('status' => 404));
            }
            //$oReturn->original = $existing;

            if (!current_user_can('edit_user', $data['ID'])) {
                error_log('REPORT: update_user');
                return new WP_Error('json_user_cannot_edit', __('Sorry, you are not allowed to edit this user.'), array('status' => 403));
            }

            $user->ID = $existing->ID;
            $lCurrentEmail = $existing->user_email;

            // Names
            if (isset($data['name'])) {
                $user->display_name = $data['name'];
            }

            if (isset($data['firstname'])) {
                $user->first_name = $data['firstname'];
            } else if (isset($data['first_name'])) {
                $user->first_name = $data['first_name'];
            }

            if (isset($data['surname'])) {
                $user->last_name = $data['surname'];
            } else if (isset($data['last_name'])) {
                $user->last_name = $data['last_name'];
            }

            if (isset($data['birthday'])) {
                $user->birthday = date("Y-m-d H:i:s", strtotime($data['birthday']));
            }

            if (isset($data['salutation'])) {
                $user->salutation = $data['salutation'];
            }

            if (isset($data['title'])) {
                $user->title = $data['title'];
            }
            // PLZ
            if (isset($data['plz'])) {
                $user->plz = $data['plz'];
            }

            // Email
            if (!empty($data['email'])) {
                $user->user_email = $data['email'];
            }

            if (isset($data['password'])) {
                $user->user_pass = $data['password'];
            }

            // Pre-flight check
            $user = apply_filters('json_pre_insert_user', $user, $data);

            if (is_wp_error($user)) {
                return $user;
            }

            // Set Role to Berufsgruppe Auswahl - because of wpmu this must be done for a blog
            if (!empty($data['professional'])) {
                if (array_key_exists($data['professional'], $this->customroles)) {
                    if ($data['professional'] != 'administrator') {
                        $user->role = $this->customroles[$data['professional']];
                    }
                }
            }

            // Update user in wordpress
            $user_id = wp_update_user($user);

            // Update email address in knews if needed
            //knews_sync_email($lCurrentEmail, $user->user_email);

            if (is_wp_error($user_id)) {
                return $user_id;
            }

            // Continue with Additional Data
            if (function_exists('xprofile_set_field_data')) {

                // Sync Wordpress Names to BPs Xprofile
                xprofile_set_field_data('Vorname', $user_id, $user->first_name);
                xprofile_set_field_data('Nachname', $user_id, $user->last_name);

                // Convert Birthday Date to useable Format
                // Converted from <rcf3339>: "Y-m-d\TH:i:sP"  to: ??
                //$this->set_format( '/^\d{4}-\d{1,2}-\d{1,2} 00:00:00$/', 'replace' ); // "Y-m-d 00:00:00"

                if (isset($data['birthday'])) {
                    //$bdate = date('F j, Y', strtotime( $data['birthday']));
                    $bdate = date('Y-m-d H:i:s', strtotime($data['birthday']));
                    xprofile_set_field_data($this->xprofile_fields['birthday'], $user_id, $bdate);
                }
                /*if (isset($data['plz'])) {
                     xprofile_set_field_data($this->xprofile_fields['plz'], $user_id, $data['plz']);
                 }*/

                // Store Additional Data into xProfile Fields if available
                foreach ($this->additional_data as $arg) {
                    if (array_key_exists($arg, $this->xprofile_fields)) {

                        // If no Data was submitted , set Field to false, --> TO RESEARCH
                        if (empty($data[$arg])) {
                            xprofile_set_field_data($this->xprofile_fields[$arg], $user_id, false);
                        } else {
                            //$value = maybe_serialize($data[ $arg ]);
                            if ($arg == "professional") {
                                if (is_array($data[$arg])) {
                                    $data[$arg] = $data[$arg][0];
                                }
                            }
                            xprofile_set_field_data($this->xprofile_fields[$arg], $user_id, $data[$arg]);
                        }

                    }
                }
            } // end xProfileFields

            // Continue with Knews fields
            if (isset($data['newsletters'])) {
                //return $data['newsletters'];

                $knews_user = new stdClass;
                $easydoc_UserClass = new easydoc_Users;

                $_array_newsletter_data = array();
                foreach ($data['newsletters'] as $key => $value) {
                    $_array_newsletter_data[$value['id']] = $value['subscription'];
                }
                $knews_user->userid = $user->ID;
                $knews_user->first_name = $user->first_name;
                $knews_user->last_name = $user->last_name;
                $knews_user->user_email = $user->user_email;
                if (isset($data['titel'])) {
                    $knews_user->title = $data['titel'];
                } else {
                    $knews_user->title = '';
                }
                if (isset($data['salutation'])) {
                    $knews_user->anrede = $data['salutation'];
                } else {
                    $knews_user->anrede = '';
                }
                //return $_array_newsletter_data;

                $knews_user->mailinglistids = $_array_newsletter_data;
                $easydoc_UserClass->subscribe_to_knews($knews_user);
            } //end knews

            // Set the password after everything if finished if needed
            if (isset($data['password']) && ($data['password'] != "")) {
                $user_id = (int)$user->ID;
                $oReturn->pwdset = true;

                $lPassword = wp_hash_password($data['password']); //  md5( $data['password'] );
                $_doupdatepw = $wpdb->update($wpdb->users, array('user_pass' => $lPassword), array('ID' => $user_id));
                if (is_wp_error($_doupdatepw)) {
                    return $_doupdatepw;
                }
                wp_cache_delete($user_id, 'users');
            }

            $oReturn->user_id = $data['ID'];
            $oReturn->plz = $user->plz;
            $oReturn->first_name = $user->first_name;
            $oReturn->last_name = $user->last_name;
            return $oReturn;
        }


    } // update_user

    protected function insert_user($data) {
        $user = new stdClass;
        global $wpdb;

        if (!empty($data['ID'])) {
            $existing = get_userdata($data['ID']);

            if (!$existing) {
                return new WP_Error('json_user_invalid_id', __('Invalid user ID.'), array('status' => 404));
            }

            if (!current_user_can('edit_user', $data['ID'])) {
                error_log('REPORT: insert_user 1');
                return new WP_Error('json_user_cannot_edit', __('Sorry, you are not allowed to edit this user.'), array('status' => 403));
            }

            $user->ID = $existing->ID;
            $update = true;
        } else {

            // Only Admins or easydocTest@proman.at can create Users
            $current_user = wp_get_current_user();
            //if ( ! current_user_can( 'create_users' ) ) {
            if ($current_user->user_email != $this->adminemail) {
                error_log('REPORT: insert_user 2');
                return new WP_Error('json_cannot_create1', __('Sorry, you are not allowed to create users.'), array('status' => 403));
            }

            $required = array(
                'password',
                'email'
            );
            foreach ($required as $arg) {
                if (empty($data[$arg])) {
                    return new WP_Error('json_missing_callback_param', sprintf(__('Missing parameter %s'), $arg), array('status' => 400));
                }
            }
            $update = false;
        }


        // Basic  details
        if (isset($data['password'])) {
            $user->user_pass = $data['password'];
            // For the Welcome email, Password must be stores in palntext
            $user->sb_we_plaintext_pass = $data['password'];
        }

        // Names
        if (isset($data['name'])) {
            $user->display_name = $data['name'];
        }

        if (isset($data['firstname'])) {
            $user->first_name = $data['firstname'];
        } else if (isset($data['first_name'])) {
            $user->first_name = $data['first_name'];
        }

        if (isset($data['surname'])) {
            $user->last_name = $data['surname'];
        } else if (isset($data['last_name'])) {
            $user->last_name = $data['last_name'];
        }

        //error_log('REPORT: create_user() last_name: ' . $user->last_name);
        //error_log('REPORT: create_user() first_name: ' . $user->first_name);

        // Generate Username
        if (isset($data['username'])) {
            $user->user_login = $data['username'];
        } else {
            $user->user_login = $this->generate_Username($user->first_name, $user->last_name);
            if (is_wp_error($user->user_login)) {
                return $user->user_login;
            }
        }

        if (isset($data['nickname'])) {
            $user->nickname = $data['nickname'];
        }

        if (!empty($data['slug'])) {
            $user->user_nicename = $data['slug'];
        }

        // URL
        if (!empty($data['URL'])) {
            $escaped = esc_url_raw($user->user_url);

            if ($escaped !== $user->user_url) {
                return new WP_Error('json_invalid_url', __('Invalid user URL.'), array('status' => 400));
            }

            $user->user_url = $data['URL'];
        }

        // Description
        if (!empty($data['description'])) {
            $user->description = $data['description'];
        }

        // Email
        if (!empty($data['email'])) {
            $user->user_email = $data['email'];
        }

        // Pre-flight check
        $user = apply_filters('json_pre_insert_user', $user, $data);

        if (is_wp_error($user)) {
            return $user;
        }

        // Set Role to Berufsgruppe Auswahl - because of wpmu this must be done for a blog
        if (!empty($data['professional'])) {
            if (array_key_exists($data['professional'], $this->customroles)) {
                if ($data['professional'] != 'administrator') {

                    $user->role = $this->customroles[$data['professional']];
                    //wp_update_user( $user );
                    //if ( add_user_to_blog( '1', $user_id, $user->role ) ) {
                    // return new WP_Error( 'insert_user', $user->role , array( 'status' => 403 ) );
                    //} else {
                    //return new WP_Error( 'insert_user', __('Failed to add USer ' ), array( 'status' => 403 ) );
                    //}
                    //	return new WP_Error( 'insert_user_role',  $this->customroles[$data['professional']] , array( 'status' => 403 ) );
                }
            }
        }


        // If Multisite is used
        if (is_multisite()) {

            // Add filter with higher priority --> does not work
            //add_filter('wpmu_signup_user_notification', 'override_wpmu_signup_user_notification', 99);

            remove_filter('bp_core_activate_account', 'bp_user_activate_field');
            remove_filter('wpmu_signup_user_notification', 'complete_registratian_and_stuff', 1, 4);
            add_filter('wpmu_signup_user_notification', '__return_false');


            // Set User Role and Blogid in stdClass $user
            $user->add_to_blog = $this->blogid;

            // wpmu_signup_user needs an array, not an object
            $user_data_array = json_decode(json_encode($user), true);
            //wpmu_signup_user( $user->user_login, $user->user_email, $user_data_array );

            wpmu_signup_user($user->user_login, $user->user_email, //$user_data_array
                array(
                    'add_to_blog' => $this->blogid,
                    'new_role' => $user->role,
                    'role' => $user->role
                ));
            switch_to_blog($this->blogid);
            //add_filter( 'wpmu_signup_user_notification', '__return_false' );

            $key = $wpdb->get_var($wpdb->prepare("SELECT activation_key FROM {$wpdb->signups} WHERE user_login = %s AND user_email = %s", $user->user_login, $user->user_email));

            $ret = wpmu_activate_signup($key);

            // New User should have been created and activated by now
            $new_user = get_user_by('email', $user->user_email);
            if (!$new_user) {
                error_log('REPORT: insert_user 3: ' . $user->user_email);
                return new WP_Error('insert_user', __('Benutzer konnte nicht angelegt werden'), array('status' => 403));
            }

            $user_id = $new_user->ID;

            $user->ID = $user_id;
            $updated_user = wp_update_user($user);
            $oReturn['updated_user'] = $updated_user;

            add_filter('wpmu_signup_user_notification', 'complete_registratian_and_stuff', 1, 4);
            add_filter('bp_core_activate_account', 'bp_user_activate_field');
            add_filter('wpmu_signup_user_notification', '__return_true');

        } else {
            // if no Multisite
            $user_id = $update ? wp_update_user($user) : wp_insert_user($user);
        }

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        // Continue with Additional Data
        // ToDO sync Buddypress Fields Vorname + Nachname
        if (function_exists('xprofile_set_field_data')) {

            // Debug
            // $field_id = xprofile_get_field_id_from_name( 'Vorname');
            // return new WP_Error( 'json_invalid_url_Xprofile','Vorname'. $field_id, array( 'status' => 400 ) );

            // Sync Wordpress Names to BPs Xprofile
            xprofile_set_field_data('Vorname', $user_id, $user->first_name);
            xprofile_set_field_data('Nachname', $user_id, $user->last_name);

            // Convert Birthday Date to useable Format
            // Converted from <rcf3339>: "Y-m-d\TH:i:sP"  to: ??
            //$this->set_format( '/^\d{4}-\d{1,2}-\d{1,2} 00:00:00$/', 'replace' ); // "Y-m-d 00:00:00"

            if (isset($data['birthday'])) {
                //$bdate = date('F j, Y', strtotime( $data['birthday']));
                $bdate = date('Y-m-d H:i:s', strtotime($data['birthday']));
                xprofile_set_field_data($this->xprofile_fields['birthday'], $user_id, $bdate);
            }

            // Store Additional Data into xProfile Fields if available
            foreach ($this->additional_data as $arg) {
                if (array_key_exists($arg, $this->xprofile_fields)) {

                    // If no Data was submitted , set Field to false, --> TO RESEARCH
                    if (empty($data[$arg])) {
                        xprofile_set_field_data($this->xprofile_fields[$arg], $user_id, false);
                    } else {
                        //$value = maybe_serialize($data[ $arg ]);
                        if ($arg == "professional") {
                            if (is_array($data[$arg])) {
                                $data[$arg] = $data[$arg][0];
                            }
                        }
                        xprofile_set_field_data($this->xprofile_fields[$arg], $user_id, $data[$arg]);
                    }

                }
            }
        } // end xProfileFields

        // Continue with Knews fields
        if (isset($data['newsletters'])) {
            //return $data['newsletters'];

            $knews_user = new stdClass;
            $easydoc_UserClass = new easydoc_Users;

            $_array_newsletter_data = array();
            foreach ($data['newsletters'] as $key => $value) {
                $_array_newsletter_data[$value['id']] = $value['subscription'];
            }
            $knews_user->userid = $user->ID;
            $knews_user->first_name = $user->first_name;
            $knews_user->last_name = $user->last_name;
            $knews_user->user_email = $user->user_email;
            if (isset($data['titel'])) {
                $knews_user->title = $data['titel'];
            } else {
                $knews_user->title = '';
            }
            if (isset($data['salutation'])) {
                $knews_user->anrede = $data['salutation'];
            } else {
                $knews_user->anrede = '';
            }
            //return $_array_newsletter_data;

            $knews_user->mailinglistids = $_array_newsletter_data;
            $easydoc_UserClass->subscribe_to_knews($knews_user);
        } //end knews

        $user->ID = $user_id;
        do_action('json_insert_user', $user, $data, $update);

        $oReturn['user_id'] = $user_id;
        return $user_id;
    } // insert_user


    // Generates a valid Username from two Strings
    // Detects Umlaute and Sonderzeichen, removes them
    // If a Valid Username cannot be generated, throws error
    function generate_Username($firstname, $surename) {

        $firstname = strip_tags(strtolower($firstname));
        $surename = strip_tags(strtolower($surename));

        $firstname = filter_var($firstname, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $surename = filter_var($surename, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

        $replacer = array(
            'ä' => 'ae',
            'à' => 'a',
            'á' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'À' => 'a',
            'Á' => 'a',
            'Â' => 'a',
            'Ã' => 'a',
            'Ä' => 'a',
            'Å' => 'a',
            'å' => 'a',
            'æ' => 'ae',
            'ü' => 'ue',
            'ù' => 'u',
            'ú' => 'u',
            'û' => 'u',
            'Ù' => 'u',
            'Ú' => 'u',
            'Û' => 'u',
            'Ü' => 'u',
            'ö' => 'o',
            'ò' => 'o',
            'ó' => 'o',
            'ô' => 'o',
            'ß' => 'ss',
            'è' => 'e',
            'é' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'È' => 'e',
            'É' => 'e',
            'Ê' => 'e',
            'Ë' => 'e',
            'Ç' => 'c',
            'Ì' => 'l',
            'Í' => 'l',
            'Î' => 'l',
            'Ï' => 'l',
            'Ñ' => 'n',
            'Ò' => 'o',
            'Ó' => 'o',
            'Ô' => 'o',
            'Õ' => 'o',
            'Ø' => '',
            'ç' => '',
            'ì' => '',
            'í' => '',
            'î' => '',
            'ï' => '',
            'ð' => 'o',
            'ñ' => 'n',
            'õ' => 'o',
            'ø' => '',
            'ý' => 'y',
            'ÿ' => 'y',
            '€' => 'e',
            '-' => '',
            ' ' => '',
            '_' => '',
        );
        foreach ($replacer as $tobereplaced => $toreplace) {
            $umlaute[] = $tobereplaced;
            $alias[] = $toreplace;
        }

        $firstname = str_replace($umlaute, $alias, $firstname);
        $firstname = preg_replace('/[^a-zA-Z0-9_ \-]/s', '', $firstname);

        $surename = str_replace($umlaute, $alias, $surename);
        $surename = preg_replace('/[^a-zA-Z0-9_ \-]/s', '', $surename);

        $username = $firstname . $surename;

        error_log('REPORT: generate_Username() 1 : ' . $username);

        $username_check = bp_core_validate_user_signup($username, 'dummy@shouldnotexistlolrofl.com');

        if (empty($username_check['errors']->errors['user_name'])) {
            return $username;
        } // if username exists, add random numbers
        else {
            $rand_nr = rand(0, 9) . rand(0, 9) . rand(0, 9);
            $username = $username . $rand_nr;
            $username_check = bp_core_validate_user_signup($username, 'dummy@shouldnotexistlolrofl.com');
            if (empty($username_check['errors']->errors['user_name'])) {
                return $username;
            } else {
                error_log('REPORT: generate_Username() 2: ' . $username);
                return new WP_Error('json_username', __('Der Benutzername ist schon in Verwendung.'), array('status' => 403));
            }
        }
    } // generate_Username


    /**
     * Delete a user, used for Testing only!
     *
     * @param int $id
     * @param bool force
     * @return true on success
     */
    public function delete_user($id, $force = false, $reassign = null) {
        $id = absint($id);

        if (empty($id)) {
            return new WP_Error('json_user_invalid_id', __('Ungültige Benutzer ID.'), array('status' => 400));
        }

        // Permissions check
        if (!current_user_can('delete_user', $id)) {
            error_log('REPORT: delete_user');
            return new WP_Error('json_user_cannot_delete', __('Sorry, you are not allowed to delete this user.'), array('status' => 403));
        }

        $user = get_userdata($id);

        if (!$user) {
            return new WP_Error('json_user_invalid_id', __('Invalid user ID.'), array('status' => 400));
        }

        if (!empty($reassign)) {
            $reassign = absint($reassign);

            // Check that reassign is valid
            if (empty($reassign) || $reassign === $id || !get_userdata($reassign)) {
                return new WP_Error('json_user_invalid_reassign', __('Invalid user ID.'), array('status' => 400));
            }
        } else {
            $reassign = null;
        }

        if (is_multisite()) {
            $result_wpmu = wpmu_delete_user($id);
        } else {
            $result = wp_delete_user($id, $reassign);
        }

        $user = get_user_by('id', $id);

        if (!$user) {
            return array('message' => __('Deleted user'));
        } else if (!$result) {
            return new WP_Error('json_cannot_delete', __('The user cannot be deleted.'), array('status' => 500));
        }
    } // delete_user

    /**
     * Reset Password and send E-Mail
     *
     * @param
     * @return response
     */
    public static function reset_pwd($email) {
        global $wpdb, $wp_hasher;

        if (empty($email)) {
            error_log('REPORT: reset_pwd 1');
            return new WP_Error('json_reset_pwd', __('Enter an E-mail address.'), array('status' => 403));
        }

        if (!strpos($email, '@')) {
            error_log('REPORT: reset_pwd 2');
            return new WP_Error('json_reset_pwd', __('Enter an e-mail address.'), array('status' => 403));
        }

        $user_data = get_user_by('email', trim($email));

        if (empty($user_data)) {
            error_log('REPORT: reset_pwd 3');
            return new WP_Error('json_reset_pwd', __('Kein Benutzer mit dieser E-Mail Adresse gefunden.'), array('status' => 403));
        }

        // redefining user_login ensures we return the right case in the email
        $user_login = $user_data->user_login;
        $user_email = $user_data->user_email;

        $allow = apply_filters('allow_password_reset', true, $user_data->ID);
        if (!$allow) {
            return new WP_Error('no_password_reset', __('Password reset is not allowed for this user'));
        } else if (is_wp_error($allow)) {
            return $allow;
        }
        // Generate something random for a password reset key.

        $key = wp_generate_password(20, false);

        /**
         * Fires when a password reset key is generated.
         *
         * @param string $user_login The username for the user.
         * @param string $key The generated password reset key.
         * @since 2.5.0
         *
         */
        do_action('retrieve_password_key', $user_login, $key);

        // Now insert the key, hashed, into the DB.
        if (empty($wp_hasher)) {
            require_once ABSPATH . WPINC . '/class-phpass.php';
            $wp_hasher = new PasswordHash(8, true);
        }
        $hashed = $wp_hasher->HashPassword($key);
        $wpdb->update($wpdb->users, array('user_activation_key' => $hashed), array('user_login' => $user_login));

        $message = __('Someone requested that the password be reset for the following account:') . "\r\n\r\n";
        $message .= network_home_url('/') . "\r\n\r\n";
        $message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
        $message .= __('If this was a mistake, just ignore this email and nothing will happen.') . "\r\n\r\n";
        $message .= __('To reset your password, visit the following address:') . "\r\n\r\n";
        $message .= '<' . network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login') . ">\r\n";

        if (is_multisite())
            $blogname = $GLOBALS['current_site']->site_name; else
            /*
             * The blogname option is escaped with esc_html on the way into the database
             * in sanitize_option we want to reverse this for the plain text arena of emails.
             */ $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

        $title = sprintf(__('[%s] Password Reset'), $blogname);

        /**
         * Filter the subject of the password reset email.
         *
         * @param string $title Default email title.
         * @since 2.8.0
         *
         */
        $title = apply_filters('retrieve_password_title', $title);
        /**
         * Filter the message body of the password reset mail.
         *
         * @param string $message Default mail message.
         * @param string $key The activation key.
         * @since 2.8.0
         *
         */
        $message = apply_filters('retrieve_password_message', $message, $key);

        if ($message && !wp_mail($user_email, wp_specialchars_decode($title), $message))
            wp_die(__('The e-mail could not be sent.') . "<br />\n" . __('Possible reason: your host may have disabled the mail() function.'));
    } // reset_pwd

}

?>
