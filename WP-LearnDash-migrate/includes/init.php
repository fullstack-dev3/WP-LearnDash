<?php
/**
 * Initialization functions for WPLMS LEARNDASH MIGRATION
 * @author      H.K.Latiyan(VibeThemes)
 * @category    Admin
 * @package     Initialization
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPLMS_LEARNDASH_INIT{

    public static $instance;
    
    public static function init(){

        if ( is_null( self::$instance ) )
            self::$instance = new WPLMS_LEARNDASH_INIT();

        return self::$instance;
    }

    private function __construct(){
    	if ( in_array( 'sfwd-lms/sfwd_lms.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) || (function_exists('is_plugin_active') && is_plugin_active( 'sfwd-lms/sfwd_lms.php'))) {
			add_action( 'admin_notices',array($this,'migration_notice' ));
			add_action('wp_ajax_migration_wp_ld_courses',array($this,'migration_wp_ld_courses'));

			add_action('wp_ajax_migration_wp_ld_course_to_wplms',array($this,'migration_wp_ld_course_to_wplms'));
            add_action('wp_ajax_revert_migrated_courses',array($this,'revert_migrated_courses'));
            add_action('wp_ajax_dismiss_message',array($this,'dismiss_message'));
		}
    }

    function migration_notice(){
    	$this->migration_status = get_option('wplms_wp_ld_migration');
        $this->revert_status = get_option('wplms_wp_ld_migration_reverted');
        if(!empty($this->migration_status && empty($this->revert_status))){
            ?>
            <div id="migration_learndash_courses_revert" class="update-nag notice ">
               <p id="revert_message"><?php printf( __('LEARNDASH Courses migrated to WPLMS: Want to revert changes %s Revert Changes Now %s Otherwise dismiss this notice.', 'wplms-ldm' ),'<a id="begin_revert_migration" class="button primary">','</a><a id="dismiss_message" href=""><i class="fa fa-times-circle-o"></i>Dismiss</a>'); ?>
               </p>
            </div>
            <style>
                #migration_learndash_courses_revert{width:97%;} 
                #dismiss_message {float:right;padding:5px 10px 10px 10px;color:#e00000;}
                #dismiss_message i {padding-right:3px;}
            </style>
            <?php wp_nonce_field('security','security'); ?>
            <script>
                jQuery(document).ready(function($){
                    $('#begin_revert_migration').on('click',function(){
                        $.ajax({
                            type: "POST",
                            url: ajaxurl,
                            data: { action: 'revert_migrated_courses', 
                                      security: $('#security').val(),
                                    },
                            cache: false,
                            success: function () {
                                $('#migration_learndash_courses_revert').removeClass('update-nag');
                                $('#migration_learndash_courses_revert').addClass('updated');
                                $('#migration_learndash_courses_revert').html('<p id="revert_message">'+'<?php _e('WPLMS - LEARNDASH MIGRATION : Migrated courses Reverted !', 'wplms-ldm' ); ?>'+'</p>');
                            }
                        });
                    });
                    $('#dismiss_message').on('click',function(){
                        $.ajax({
                            type: "POST",
                            url: ajaxurl,
                            data: { action: 'dismiss_message', 
                                      security: $('#security').val(),
                                    },
                            cache: false,
                            success: function () {
                                
                            }
                        });
                    });
                });
            </script>
            <?php
            return;
        }        
        
        $check = 1;
        if(!function_exists('woocommerce')){
            $check = 0;
            ?>
            <div class="welcome-panel" id="welcome_ld_panel" style="padding-bottom:20px;width:96%">
                <h1><?php echo __('Please note the following before starting migration:','wplms-lp'); ?></h1>
                <ol>
                    <li><?php echo __('Woocommerce must be activated if using paid courses.','wplms-lp'); ?></li>
                    <li><?php echo __('WPLMS vibe custom types plugin must be activated.','wplms-lp'); ?></li>
                    <li><?php echo __('WPLMS vibe course module plugin must be activated.','wplms-lp'); ?></li>
                </ol>
                <p><?php echo __('If all the above plugins are activated then please click on the button below to proceed to migration proccess','wplms-lp'); ?></p>
                <form method="POST">
                    <input name="click" type="submit" value="<?php echo __('Click Here','wplms-lp'); ?>" class="button">
                </form>
            </div>
            <?php
        }
        if(isset($_POST['click'])){
            $check = 1;
            ?> <style> #welcome_ld_panel{display:none;} </style> <?php
        }

    	if(empty($this->migration_status) && $check){
    		?>
    		<div id="migration_learndash_courses" class="error notice ">
		       <p id="ldm_message"><?php printf( __('Migrate Learndash coruses to WPLMS %s Begin Migration Now %s', 'wplms-ldm' ),'<a id="begin_wplms_learndash_migration" class="button primary">','</a>'); ?>
		       	
		       </p>
		   <?php wp_nonce_field('security','security'); ?>
		        <style>.wplms_ld_progress .bar{-webkit-transition: width 0.5s ease-in-out;
    -moz-transition: width 1s ease-in-out;-o-transition: width 1s ease-in-out;transition: width 1s ease-in-out;}</style>
		        <script>
		        	jQuery(document).ready(function($){
		        		$('#begin_wplms_learndash_migration').on('click',function(){
			        		$.ajax({
			                    type: "POST",
			                    dataType: 'json',
			                    url: ajaxurl,
			                    data: { action: 'migration_wp_ld_courses', 
			                              security: $('#security').val(),
			                            },
			                    cache: false,
			                    success: function (json) {

			                    	$('#migration_learndash_courses').append('<div class="wplms_ld_progress" style="width:100%;margin-bottom:20px;height:10px;background:#fafafa;border-radius:10px;overflow:hidden;"><div class="bar" style="padding:0 1px;background:#37cc0f;height:100%;width:0;"></div></div>');

			                    	var x = 0;
			                    	var width = 100*1/json.length;
			                    	var number = 0;
									var loopArray = function(arr) {
									    wpld_ajaxcall(arr[x],function(){
									        x++;
									        if(x < arr.length) {
									         	loopArray(arr);   
									        }
									    }); 
									}
									
									// start 'loop'
									loopArray(json);

									function wpld_ajaxcall(obj,callback) {
										
				                    	$.ajax({
				                    		type: "POST",
						                    dataType: 'json',
						                    url: ajaxurl,
						                    data: {
						                    	action:'migration_wp_ld_course_to_wplms', 
						                        security: $('#security').val(),
						                        id:obj.id,
						                    },
						                    cache: false,
						                    success: function (html) {
						                    	number = number + width;
						                    	$('.wplms_ld_progress .bar').css('width',number+'%');
						                    	if(number >= 100){
                                                    $('#migration_learndash_courses').removeClass('error');
                                                    $('#migration_learndash_courses').addClass('updated');
                                                    $('#ldm_message').html('<strong>'+x+' '+'<?php _e('Courses successfully migrated from Learndash to WPLMS','wplms-ldm'); ?>'+'</strong>');
										        }
						                    }
				                    	});
									    // do callback when ready
									    callback();
									}
			                    }
			                });
		        		});
		        	});
		        </script>
		    </div>
		    <?php
    	}
    }

    function migration_wp_ld_courses(){
    	if ( !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],'security') || !is_user_logged_in()){
         	_e('Security check Failed. Contact Administrator.','wplms-ldm');
         	die();
      	}

      	global $wpdb;
		$courses = $wpdb->get_results("SELECT id,post_title FROM {$wpdb->posts} where post_type='sfwd-courses'");
		$json=array();
		foreach($courses as $course){
			$json[]=array('id'=>$course->id,'title'=>$course->post_title);
		}
		update_option('wplms_wp_ld_migration',1);
		
		$this->migrate_posts();

		print_r(json_encode($json));
		die();
    }

    function revert_migrated_courses(){
        if ( !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],'security') || !is_user_logged_in()){
            _e('Security check Failed. Contact Administrator.','wplms-ldm');
            die();
        }
        update_option('wplms_wp_ld_migration_reverted',1);
        $this->revert_migrated_posts();
        die();
    }

    function dismiss_message(){
        if ( !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],'security') || !is_user_logged_in()){
            _e('Security check Failed. Contact Administrator.','wplms-ldm');
            die();
        }
        update_option('wplms_wp_ld_migration_reverted',1);
        die();
    }

    function migrate_posts(){

    	global $wpdb;
        $post_types = $wpdb->get_results("SELECT ID, post_type FROM {$wpdb->posts} WHERE post_type IN('sfwd-lessons','sfwd-topic')");

        update_option('ld_wplms_post_types',$post_types);

    	$wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'course' WHERE post_type = 'sfwd-courses'");
    	$wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'unit' WHERE post_type = 'sfwd-lessons' OR post_type = 'sfwd-topic'");
        $wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'quiz' WHERE post_type = 'sfwd-quiz'");
        $wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'certificate' WHERE post_type = 'sfwd-certificates'");

    }

    function revert_migrated_posts(){
       global $wpdb;
       $revert = get_option('ld_wplms_post_types',true);
       foreach($revert as $revert_unit){
            if($revert_unit->post_type == 'sfwd-lessons'){
                $wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'sfwd-lessons' WHERE post_type = 'unit' AND ID = $revert_unit->ID");
            }elseif($revert_unit->post_type == 'sfwd-topic'){
                $wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'sfwd-topic' WHERE post_type = 'unit' AND ID = $revert_unit->ID");
            }
       }

        $wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'sfwd-courses' WHERE post_type = 'course'");
        $wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'sfwd-quiz' WHERE post_type = 'quiz'");
        $wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'sfwd-certificates' WHERE post_type = 'certificate'");

        //Revert post_content for the questions
        $data = $wpdb->get_results("SELECT post_id,meta_value FROM {$wpdb->postmeta} where meta_key='ld_que_post_content'",ARRAY_A);
        if(!empty($data)){
            foreach($data as $post){
                $postarr = array('ID' => $post['post_id'],'post_content' => $post['meta_value'] );
                wp_update_post($postarr,true);
            }
        }
    }

    function migration_wp_ld_course_to_wplms(){
    	if ( !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],'security') || !is_user_logged_in()){
         	_e('Security check Failed. Contact Administrator.','wplms-ldm');
         	die();
      	}

    	global $wpdb;
		$this->migrate_course_settings($_POST['id']);
		
    }

    function migrate_course_settings($course_id){
    	$settings = get_post_meta($course_id,'_sfwd-courses',true);
    	if(!empty($settings)){
    		if(!empty($settings['sfwd-courses_course_materials'])){
    			update_post_meta($course_id,'vibe_course_instructions',$settings['sfwd-courses_course_materials']);
    		}

    		if(!empty($settings['sfwd-courses_custom_button_url'])){
    			update_post_meta($course_id,'vibe_course_external_link',$settings['sfwd-courses_custom_button_url']);
    		}

    		if(!empty($settings['sfwd-courses_course_price_type'])){
	    		if($settings['sfwd-courses_course_price_type'] == 'open' || $settings['sfwd-courses_course_price_type'] == 'free'){
	    			update_post_meta($course_id,'vibe_course_free','S');
	    		}

                if($settings['sfwd-courses_course_price_type'] == 'closed'){
                    update_post_meta($course_id,'vibe_course_free','H');
                }

	    		if($settings['sfwd-courses_course_price_type'] == 'paynow'){
	    			update_post_meta($course_id,'vibe_course_free','H');
	    			//Create product and connect for price.
                    if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
                        $this->course_pricing($settings,$course_id);
                    }
	    		}
	    	}

    		if(!empty($settings['sfwd-courses_expire_access'])){
    			if($settings['sfwd-courses_expire_access'] == 'on'){
    				update_post_meta($course_id,'vibe_course_duration_parameter',86400);
    				if(!empty($settings['sfwd-courses_expire_access_days'])){
    					update_post_meta($course_id,'vibe_duration',$settings['sfwd-courses_expire_access_days']);
    				}
    			}else{
    				update_post_meta($course_id,'vibe_duration',9999);
    			}
    		}else{
                update_post_meta($course_id,'vibe_duration',9999);
            }
            
    		if(!empty($settings['sfwd-courses_course_prerequisite'])){
    			update_post_meta($course_id,'vibe_pre_course',$settings['sfwd-courses_course_prerequisite']);
    		}

    		if(!empty($settings['sfwd-courses_certificate'])){
    			update_post_meta($course_id,'vibe_course_certificate',$settings['sfwd-courses_certificate']);
    		}
    	}
    	$this->course_id = $_POST['id'];
        if(!empty($settings['sfwd-courses_course_lesson_orderby'])){
            $this->unit_order_by = $settings['sfwd-courses_course_lesson_orderby'];
        }
    	if(!empty($settings['sfwd-courses_course_lesson_order'])){
            $this->unit_order = $settings['sfwd-courses_course_lesson_order'];
        }
    	
		$this->build_curriculum($_POST['id']);
    }

    function build_curriculum($course_id){
    	global $wpdb;
    	$orderby = 'menu_order';
    	if(!empty($this->unit_order_by)){
    		switch($this->unit_order_by){
    			case 'date':$orderby = 'post_date';
    			break;
    			case 'title': $orderby = 'post_title';
    			break;
    		}
    	}
    	$order = 'DESC';
    	if(!empty($this->unit_order)){
    		$order = $this->unit_order;
    	}
    	$this->unit_order_by; $this->unit_order;

    	$lessons_topics_quizzes = $wpdb->get_results("SELECT DISTINCT m.post_id as id,p.post_type as type,p.post_title as title, p.$orderby FROM {$wpdb->postmeta} as m LEFT JOIN {$wpdb->posts} as p ON p.id = m.post_id WHERE m.meta_value = $course_id AND m.meta_key = 'course_id' ORDER BY p.$orderby $order");

    	if(!empty($lessons_topics_quizzes)){
    		foreach($lessons_topics_quizzes as $unit){
    			switch($unit->type){
    				case 'unit':
                        $this->migrate_unit_settings($unit->id);
    					$after_unit = get_post_meta($unit->id,'lesson_id',true);
                        if(!empty($after_unit)){
                            //Course TOPIC UNIT 
                            $unit_key = array_search($after_unit,$curriculum);
                            if($unit_key !== false){
                                array_splice( $curriculum, ($unit_key+1), 0, $unit->id );
                            }else{
                                if(empty($this->store_units[$after_unit])){
                                    $this->store_units[$after_unit] = array($unit->id);    
                                }else{
                                    $this->store_units[$after_unit][] = $unit->id; 
                                }
                            }
                            
                        }else{
                            /*
                            LESSON UNIT ID; unit ID is LESSON ID
                            */
                            $curriculum[] = $unit->title;
                            $curriculum[] = $unit->id;
                        }
    				break;
    				case 'quiz':
                    /* $unit->id = $quiz_id */
                        $after_unit = get_post_meta($unit->id,'lesson_id',true);
                        if(!empty($after_unit)){
                            $quiz_key = array_search($after_unit,$curriculum);
                            if($quiz_key !== false){
                                array_splice( $curriculum, ($quiz_key+1), 0, $unit->id );
                            }else{
                                if(empty($this->store_quiz[$after_unit])){
                                    $this->store_quiz[$after_unit] = array($unit->id);    
                                }else{
                                    $this->store_quiz[$after_unit][] = $unit->id; 
                                }
                            }
                            
                        }else{
                            $curriculum[] = $unit->id;
                        }
                        
                        $this->migrate_quiz_settings($unit->id);
                        $this->migrate_questions($unit->id);
                        
    				break;
    			}
    		}

            if(!empty($this->store_units)){
                foreach($this->store_units as $parent_unit_id => $unit_ids){
                    if(!empty($unit_ids)){
                        $parent_unit_key = array_search($parent_unit_id,$curriculum);
                        array_splice( $curriculum, ($parent_unit_key+1), 0, $unit_ids );
                    }
                }
            }
            if(!empty($this->store_quiz)){
                foreach($this->store_quiz as $parent_quiz_id => $quiz_ids){
                    if(!empty($quiz_ids)){
                        $parent_quiz_key = array_search($parent_quiz_id,$curriculum);
                        array_splice( $curriculum, ($parent_quiz_key+1), 0, $quiz_ids );
                    }
                }
            }

    	}
    	update_post_meta($course_id,'vibe_course_curriculum',$curriculum);
    }
    
    function migrate_unit_settings($unit_id){
        $settings = get_post_meta($unit_id,'_sfwd-topic',true);
        if(!empty($settings)){
            if(!empty($settings['sfwd-topic_forced_lesson_time'])){
                update_post_meta($unit_id,'vibe_duration',$settings['sfwd-topic_forced_lesson_time']);
            }
        }

        $settings = get_post_meta($unit_id,'_sfwd-lessons',true);
        if(!empty($settings)){
            if(!empty($settings['sfwd-lessons_forced_lesson_time'])){
                update_post_meta($unit_id,'vibe_duration',$settings['sfwd-lessons_forced_lesson_time']);
            }
            if(!empty($settings['sfwd-lessons_visible_after_specific_date'])){
                update_post_meta($unit_id,'vibe_access_date',$settings['sfwd-lessons_visible_after_specific_date']);
            }
        }
    }

    function migrate_quiz_settings($quiz_id){
        global $wpdb;
        $settings = get_post_meta($quiz_id,'_sfwd-quiz',true);
        if(!empty($settings)){
            if(!empty($settings['sfwd-quiz_course'])){
                update_post_meta($quiz_id,'vibe_quiz_course',$settings['sfwd-quiz_course']);
            }

            if(!empty($settings['sfwd-quiz_repeats'])){
                update_post_meta($quiz_id,'vibe_quiz_retakes',$settings['sfwd-quiz_repeats']);
            }

            if(!empty($settings['sfwd-quiz_quiz_pro'])){
                $new_quiz_id = $settings['sfwd-quiz_quiz_pro'];
                $quizzes = $wpdb->get_results("SELECT result_text, time_limit, question_random FROM {$wpdb->prefix}wp_pro_quiz_master WHERE id = $new_quiz_id");

                if(!empty($quizzes)){
                    foreach($quizzes as $quiz){
                        if(!empty($quiz->result_text)){
                            update_post_meta($quiz_id,'vibe_quiz_message',$quiz->result_text);
                        }
                        if(!empty($quiz->time_limit)){
                            update_post_meta($quiz_id,'vibe_duration',$quiz->time_limit);
                        }else{
                            update_post_meta($quiz_id,'vibe_duration',9999);
                        }
                        if(!empty($quiz->question_random)){
                            update_post_meta($quiz_id,'vibe_quiz_random','S');
                        }
                    }
                }
            }
        }
    }

    function migrate_questions($quiz_id){
        global $wpdb;
        $settings = get_post_meta($quiz_id,'_sfwd-quiz',true);
        if(!empty($settings)){
            if(!empty($settings['sfwd-quiz_quiz_pro'])){
                $ld_quiz_id = $settings['sfwd-quiz_quiz_pro'];

                $questions = $wpdb->get_results("SELECT title, points, question, correct_msg, tip_enabled, tip_msg, answer_type, answer_data FROM {$wpdb->prefix}wp_pro_quiz_question WHERE quiz_id = $ld_quiz_id");
                $quiz_questions = array('ques'=>array(),'marks'=>array());
                if(!empty($questions)){
                    foreach($questions as $question){
                        $args = array(
                            'post_type'=>'question',
                            'post_status'=>'publish',
                            'post_title'=>$question->title,
                            'post_content'=>$question->question
                        );
                        $question_id = wp_insert_post($args);
                        $quiz_questions['ques'][]=$question_id;
                        $quiz_questions['marks'][]=$question->points;

                        if($question->tip_enabled){
                            if(!empty($question->tip_msg))
                                update_post_meta($question_id,'vibe_question_hint',$question->question_answer_hint);
                        }

                        if(!empty($question->correct_msg))
                            update_post_meta($question_id,'vibe_question_explaination',$question->correct_msg);

                        if($question->answer_type == 'free_answer')
                            $question->answer_type = 'largetext';
                        if($question->answer_type == 'sort_answer')
                            $question->answer_type = 'sort';
                        if($question->answer_type == 'matrix_sort_answer')
                            $question->answer_type = 'match';
                        if($question->answer_type == 'cloze_answer')
                            $question->answer_type = 'fillblank';
                        if($question->answer_type == 'assessment_answer')
                            $question->answer_type = 'assessment';
                        
                        if($question->answer_type != 'largetext' && $question->answer_type != 'assessment' && $question->answer_type != 'fillblank'){
                            $ans_data = unserialize($question->answer_data);

                            if($question->answer_type == 'sort'){

                                $opt_arr = Array();
                                $ans_arr = Array();
                                foreach($ans_data as $and => $data) {
                                    $options = $this->accessProtected($data, '_answer');
                                    $opt_arr[] =  $options;
                                    $ans_arr[] =  $and + 1;
                                }
                                $correct_answer = implode(',', $ans_arr);
                                update_post_meta($question_id,'vibe_question_options',$opt_arr);
                                update_post_meta($question_id,'vibe_question_answer',$correct_answer);
                            }

                            if($question->answer_type == 'match'){
                                $opt_arr = Array();
                                $ans_arr = Array();
                                $ld_que_post_content = get_post_field('post_content',$question_id);
                                update_post_meta($question_id,'ld_que_post_content',$ld_que_post_content);
                                $content = $ld_que_post_content;

                                $match_list = '<br />[match]<ul>';
                                foreach($ans_data as $and => $data) {
                                    $match = $this->accessProtected($data, '_answer');
                                    $match_list .='<li>'.$match.'</li>';
                                    $matched_ans = $this->accessProtected($data, '_sortString');
                                    $opt_arr[] =  $matched_ans;
                                    $ans_arr[] =  $and + 1;
                                }
                                $match_list .= '</ul>[/match]';
                                $content .= $match_list;
                                $post = array('ID' => $question_id,'post_content' => $content );
                                wp_update_post($post,true);

                                $correct_answer = implode(',', $ans_arr);
                                update_post_meta($question_id,'vibe_question_options',$opt_arr);
                                update_post_meta($question_id,'vibe_question_answer',$correct_answer);
                            }

                            if($question->answer_type == 'single' || $question->answer_type == 'multiple'){

                                $opt_arr = Array();
                                $ans_arr = Array();
                                $ans_data = unserialize($question->answer_data);
                                foreach($ans_data as $and => $data) {
                                    $options = $this->accessProtected($data, '_answer');
                                    $opt_arr[] =  $options;
                                    $ans = $this->accessProtected($data, '_correct');
                                    if($ans == 1) {
                                        $ans_arr[] =  $and + 1;
                                    }
                                }
                                $correct_answer = implode(',', $ans_arr);
                                update_post_meta($question_id,'vibe_question_options',$opt_arr);
                                update_post_meta($question_id,'vibe_question_answer',$correct_answer);
                            }
                        }

                        if($question->answer_type == 'fillblank'){
                            $opt_arr = Array();
                            $ans_arr = Array();
                            $ans_data = unserialize($question->answer_data);
                            foreach($ans_data as $and => $data) {
                                $que_content = $this->accessProtected($data, '_answer');
                                preg_match_all('/{(.*)+}/', $que_content, $out);
                                foreach ($out[0] as $key => $answer) {
                                    $ans_arr[] = $answer;
                                }
                                $correct_answer = implode('|', $ans_arr);
                                update_post_meta($question_id,'vibe_question_answer',$correct_answer);
                                $q_content = preg_replace('/{(.*)+}/', '[fillblank]', $que_content);
                                $ld_que_post_content = get_post_field('post_content',$question_id);
                                update_post_meta($question_id,'ld_que_post_content',$ld_que_post_content);
                                $content = $ld_que_post_content;

                                $fill_blank = '<br />';
                                $fill_blank .= $q_content;
                                $content .= $fill_blank;
                                $post = array('ID' => $question_id,'post_content' => $content );
                                wp_update_post($post,true);
                            }
                        }
                        update_post_meta($question_id,'vibe_question_type',$question->answer_type);
                    }
                    update_post_meta($quiz_id,'vibe_quiz_questions',$quiz_questions);
                }
            }
        }
    }

    function accessProtected($obj, $prop) {
        if(class_exists('ReflectionClass')) {
            $reflection = new ReflectionClass($obj);
            $property = $reflection->getProperty($prop);
            $property->setAccessible(true);
            return $property->getValue($obj);
        }
    }


    function course_pricing($settings,$course_id){

        if(!empty($settings['sfwd-courses_course_price'])){

            $post_args=array('post_type' => 'product','post_status'=>'publish','post_title'=>get_the_title($course_id));
            $product_id = wp_insert_post($post_args);
            update_post_meta($product_id,'vibe_subscription','H');

            update_post_meta($product_id,'_price',$settings['sfwd-courses_course_price']);

            wp_set_object_terms($product_id, 'simple', 'product_type');
            update_post_meta($product_id,'_visibility','visible');
            update_post_meta($product_id,'_virtual','yes');
            update_post_meta($product_id,'_downloadable','yes');
            update_post_meta($product_id,'_sold_individually','yes');

            $max_seats = get_post_meta($course_id,'vibe_max_students',true);
            if(!empty($max_seats) && $max_seats < 9999){
                update_post_meta($product_id,'_manage_stock','yes');
                update_post_meta($product_id,'_stock',$max_seats);
            }
            
            $courses = array($course_id);
            update_post_meta($product_id,'vibe_courses',$courses);
            update_post_meta($course_id,'vibe_product',$product_id);

            $thumbnail_id = get_post_thumbnail_id($course_id);
            if(!empty($thumbnail_id))
                set_post_thumbnail($product_id,$thumbnail_id);
        }
    }
}

WPLMS_LEARNDASH_INIT::init();