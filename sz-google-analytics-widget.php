<?php
/*
Plugin Name: SubZane Google Analytics Plugin
Plugin URI: http://www.andreasnorman.se/wordpress-plugins/sz-google-analytics-plugin/
Description: This widget displays the most popular posts on your blog according to Google Analytics. You'll need to install "Google Analytics Dashboard Plugin" in order for this to work.
Author: Andreas Norman
Version: 0.6.1
Author URI: http://www.andreasnorman.se
*/

function sz_google_analytics_widget_init() {

	if ( !function_exists('register_sidebar_widget') )
		return;
		
	function sz_google_analytics_popular_posts_widget($args) {
		extract($args);
		$options = get_option('sz_google_analytics_widget');
		$title = empty($options['title']) ? 'Most popular posts' : $options['title'];
		$showpages = empty($options['showpages']) ? 5 : $options['showpages'];
		$trim = $options['trim'];
		$onclick_event = empty($options['onclick']) ? '' : ' onclick="'.$options['onclick'].'"';
		$nofollow_rel = empty($options['nofollow']) ? '' : ' rel="'.$options['nofollow'].'"';
		$ignore = str_replace(' ', '', $options['ignore']);
		$what = empty($options['what']) ? 'lastweek' : $options['what'];
		
		$pages = sz_google_analytics_getMostPopular($what, $start_date, $end_date, $showpages);

		echo $before_widget;
		echo $before_title . $title . $after_title;
		echo "<ul>";
		foreach($pages as $page) {
			if (!empty($trim)) {
				if ($howtrim == 'replace') {
					$title = str_replace($trim, '', $page['title']);
				} else {
					$title = preg_replace($trim, '', $page['title']);
				}
			} else {
				$title = $page['title'];
			}
			echo '<li><a '.$nofollow_rel.$onclick_event.' href="' . $page['url'] . '">' . $title . '</a></li>';
		}
		echo "</ul>";
		echo '<div class="sz-credits"><a target="_blank" href="http://www.andreasnorman.se/?utm_source=GoogleAnalyticsPlugin&utm_medium=WidgetLink&utm_campaign=GoogleAnalyticsPlugin">Plugin by Andreas Norman</a></div>';
		echo $after_widget;
	}
	
	function sz_google_analytics_ListMostPopular() {
		$options = get_option('sz_google_analytics_settings');
		$title = empty($options['title']) ? 'Most popular posts' : $options['title'];
		$showpages = empty($options['showpages']) ? 5 : $options['showpages'];
		$trim = $options['trim'];
		$onclick_event = empty($options['onclick']) ? '' : ' onclick="'.$options['onclick'].'"';
		$nofollow_rel = empty($options['nofollow']) ? '' : ' rel="'.$options['nofollow'].'"';
		$ignore = str_replace(' ', '', $options['ignore']);
		$what = empty($options['what']) ? 'lastweek' : $options['what'];
		$howtrim =  empty($options['howtrim']) ? 'replace' : $options['howtrim'];
		
		$pages = sz_google_analytics_getMostPopular($what, $start_date, $end_date, $showpages);
		echo "<ul>";
		foreach($pages as $page) {
			if (!empty($trim)) {
				if ($howtrim == 'replace') {
					$title = str_replace($trim, '', $page['title']);
				} else {
					$title = preg_replace($trim, '', $page['title']);
				}
			} else {
				$title = $page['title'];
			}
			echo '<li><a '.$nofollow_rel.$onclick_event.' href="' . $page['url'] . '">' . $title . '</a></li>';
		}
		echo "</ul>";
	}		
	
	function sz_google_analytics_getMostPopular($what, $start_date, $end_date, $showpages) {
		$options = get_option('sz_google_analytics_widget');
		$ignoreArray = explode(",", $ignore);
		$i = 1;

		if ($what == 'lastweek') {
			list($start_date, $end_date) = sz_google_analytics_getweek(-1);
		} else if ($what == 'lastmonth') {
			list($start_date, $end_date) = sz_google_analytics_getmonth(-1);
		} else if ($what == 'thisweek') {
			list($start_date, $end_date) = sz_google_analytics_getweek(0);
		} else if ($what == 'thismonth') {
			list($start_date, $end_date) = sz_google_analytics_getmonth(0);
		} else if ($what == 'thisyear') {
			$start_date = date('Y') . '-01-01';
			$end_date = date('Y-m-d');
		} else if ($what == 'lastyear') {
			$start_date = (date('Y')-1) . '-01-01';
			$end_date = (date('Y')-1) . '-12-31';
		}
		//echo $start_date.'<br>';
		//echo $end_date.'<br>';
		// ga:pageTitle!=(not set);ga:pagePath!=/
		$login = new GADWidgetData();
		$ga = new GALib($login->auth_token, $login->account_id, 60);
		$pages = $ga->complex_report_query($start_date, $end_date, array('ga:pagePath', 'ga:pageTitle'), array('ga:pageviews'), array('-ga:pageviews'), array('ga:pageTitle!=(not set);ga:pagePath!=/'));
		/*
		echo '<pre>';
		print_r($pages);
		echo '</pre>';
		*/
		foreach($pages as $page) {
			$url = $page['value'];
			//$link = '<li><a '.$nofollow_rel.$onclick_event.' href="' . $url . '">' . $title . '</a></li>';
			// Try and get page & post from url
			$page_object = get_page_by_path($url);
			if ($page_object) {
				if (!in_array($page_object->ID, $ignoreArray)) {
					$popularpages[$i]['title'] = $page['children']['value'];
					$popularpages[$i]['url'] = $url;
					$i++;
				}
			} else { // Must be a post then
				$url_array = explode('/', $url);
				$WPPageObject = get_posts('name='.$url_array[count($url_array)-2]); // We need the penultimate item from the array since the last one is empty
				if (isset($WPPageObject[0]->ID)) {
					if (!in_array($WPPageObject[0]->ID, $ignoreArray)) {
						$popularpages[$i]['title'] = $page['children']['value'];
						$popularpages[$i]['url'] = $url;
						$i++;
					}
				}
			}
			if($i > $showpages) break;
		}
		return $popularpages; 
	}

	function sz_google_analytics_settings() {
		$options = get_option('sz_google_analytics_settings');
		$categories = get_categories();

		if ( isset($_POST['sz_google_analytics_widget_submit']) ) {
			$options['showpages'] = $_POST['sz_google_analytics_showpages'];
			$options['trim'] = strip_tags(stripslashes($_POST['sz_google_analytics_trim']));
			$options['onclick'] = strip_tags(stripslashes($_POST['sz_google_analytics_onclick']));
			$options['nofollow'] = $_POST['sz_google_analytics_nofollow'];
			$options['ignore'] = $_POST['sz_google_analytics_ignore'];
			$options['what'] = $_POST['sz_google_analytics_what'];
			$options['howtrim'] = $_POST['sz_google_analytics_howtrim'];
			
			update_option('sz_google_analytics_settings', $options);
		}
		$options = get_option('sz_google_analytics_settings');

		$showpages = empty($options['showpages']) ? 5 : $options['showpages'];
		$trim = $options['trim'];
		$onclick = $options['onclick'];
		$ignore = str_replace(' ', '', $options['ignore']);
		$what = $options['what'];
		$nofollow = empty($options['nofollow']) ? 0 : $options['nofollow'];
		$howtrim =  empty($options['howtrim']) ? 'replace' : $options['howtrim'];
	  ?>

	   <div class="wrap">
	    <h2>SubZane Google Analytics Settings</h2>
			<p>Before this plugin can work you'll need to install <a href="http://wordpress.org/extend/plugins/google-analytics-dashboard/" target="_blank">Google Analytics Dashboard</a></p>
			<p>These settings are not used by the widget. You'll need to configure the widget as well if you plan on using it.</p>
			<p>Use the function <b><?php echo htmlspecialchars("<?php sz_google_analytics_ListMostPopular() ?>")?></b> to echo out a list anywhere on your page.</p>
			
		  <form method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
			<?php
			echo '
				<label style="line-height: 35px; display: block;" for="sz_google_analytics_showpages">
					' . __('Number of posts/pages in list:') . '<br/>
					<input style="width: 200px;" id="sz_google_analytics_showpages" name="sz_google_analytics_showpages" type="text" value="'.$showpages.'" />
				</label>

				<label style="line-height: 35px; display: block;">
					' . __('What to display:') . '<br/>
					<select name="sz_google_analytics_what" id="sz_google_analytics_what">
						<option value="lastweek" '.($what=='lastweek'?'selected="selected"':'').' >Top pages last week</option>
						<option value="lastmonth" '.($what=='lastmonth'?'selected="selected"':'').' >Top pages last month</option>
						<option value="thisweek" '.($what=='thisweek'?'selected="selected"':'').' >Top pages this week</option>
						<option value="thismonth" '.($what=='thismonth'?'selected="selected"':'').' >Top pages this month</option>
						<option value="lastyear" '.($what=='lastyear'?'selected="selected"':'').' >Top pages last year</option>
						<option value="thisyear" '.($what=='thisyear'?'selected="selected"':'').' >Top pages this year</option>
					</select>
				</label>

				<label style="line-height: 35px; display: block;">
					' . __('How to trim titles:') . '<br/>
					<select name="sz_google_analytics_howtrim" id="sz_google_analytics_howtrim">
						<option value="regexp" '.($howtrim=='regexp'?'selected="selected"':'').' >Regular expression</option>
						<option value="replace" '.($howtrim=='replace'?'selected="selected"':'').' >Simple trim</option>
					</select>
				</label>

				<label style="line-height: 35px; display: block;" for="sz_google_analytics_trim">
					' . __('String to trim off the titles:') . '<br/>
					<input style="width: 200px;" id="sz_google_analytics_trim" name="sz_google_analytics_trim" type="text" value="'.$trim.'" />
				</label>

				<label style="line-height: 35px; display: block;" for="sz_google_analytics_onclick">
					' . __('On-click event (enter your GA trackEvent here):') . '<br/>
					<input style="width: 200px;" id="sz_google_analytics_onclick" name="sz_google_analytics_onclick" type="text" value="'.$onclick.'" />
				</label>

				<label style="line-height: 35px; display: block;" for="sz_google_analytics_nofollow">
				<input type="checkbox" id="sz_google_analytics_nofollow" '.($nofollow==1?'checked="checked"':'').' name="sz_google_analytics_nofollow" type="text" value="1" />
					' . __('Nofollow on links') . '
				</label>

				<label style="line-height: 35px; display: block;" for="sz_google_analytics_ignore">
					' . __('Posts &amp; pages to ignore (enter ID separated by a comma):') . '<br/>
					<input style="width: 200px;" id="sz_google_analytics_ignore" name="sz_google_analytics_ignore" type="text" value="'.$ignore.'" />
				</label>

				<input type="hidden" id="sz_google_analytics_widget_submit" name="sz_google_analytics_widget_submit" value="1" />
			';		
			?>

			<input type="submit" name="Spara" value="Spara ändringar" id="Spara">
	   </form>
	</div>	
	<?php
	}

	
	function sz_google_analytics_getweek($week_index) {
	    if ($week_index < 0) {
				$ts = strtotime("$week_index week");
	    } else {
		    $ts = strtotime("now");
	    }
			
	    $start = (date('w', $ts) == 0) ? $ts : strtotime('last sunday', $ts);
	    return array(date('Y-m-d', $start),
	                 date('Y-m-d', strtotime('next saturday', $start)));
	}
	
	function sz_google_analytics_getmonth($month_index) {
    if ($month_index < 0) {
			$ts = strtotime("$month_index month");
			$end = date('Y-m-t', strtotime($ts));
    } else {
			$end = date('Y-m-d');
	    $ts = strtotime("now");
    }
	    return array(date('Y-m-01', strtotime('now', $ts)),
	                 $end);
	}

	function sz_google_analytics_popular_posts_widget_control() {
		if ( isset($_POST['sz_google_analytics_widget_submit']) ) {
			$options['title'] = strip_tags(stripslashes($_POST['sz_google_analytics_widget_title']));
			$options['showpages'] = $_POST['sz_google_analytics_showpages'];
			//$options['range'] = $_POST['sz_google_analytics_range'];
			$options['trim'] = strip_tags(stripslashes($_POST['sz_google_analytics_trim']));
			$options['onclick'] = strip_tags(stripslashes($_POST['sz_google_analytics_onclick']));
			$options['nofollow'] = $_POST['sz_google_analytics_nofollow'];
			$options['ignore'] = $_POST['sz_google_analytics_ignore'];
			$options['what'] = $_POST['sz_google_analytics_what'];
			$options['howtrim'] = $_POST['sz_google_analytics_howtrim'];
			
			update_option('sz_google_analytics_widget', $options);
		}
		$options = get_option('sz_google_analytics_widget');

		$title = empty($options['title']) ? 'Most popular posts' : $options['title'];
		$showpages = empty($options['showpages']) ? 5 : $options['showpages'];
		//$range = empty($options['range']) ? 7 : $options['range'];
		$trim = $options['trim'];
		$onclick = $options['onclick'];
		$ignore = str_replace(' ', '', $options['ignore']);
		$what = $options['what'];
		$nofollow = empty($options['nofollow']) ? 0 : $options['nofollow'];
		$howtrim =  empty($options['howtrim']) ? 'replace' : $options['howtrim'];

		echo '
			<label style="line-height: 35px; display: block;" for="sz_google_analytics_widget_title">
				' . __('Title:') . '<br/>
				<input style="width: 200px;" id="sz_google_analytics_widget_title" name="sz_google_analytics_widget_title" type="text" value="'.$title.'" />
			</label>

			<label style="line-height: 35px; display: block;" for="sz_google_analytics_showpages">
				' . __('Number of posts/pages in list:') . '<br/>
				<input style="width: 200px;" id="sz_google_analytics_showpages" name="sz_google_analytics_showpages" type="text" value="'.$showpages.'" />
			</label>

			<label style="line-height: 35px; display: block;">
				' . __('What to display:') . '<br/>
				<select name="sz_google_analytics_what" id="sz_google_analytics_what">
					<option value="lastweek" '.($what=='lastweek'?'selected="selected"':'').' >Top pages last week</option>
					<option value="lastmonth" '.($what=='lastmonth'?'selected="selected"':'').' >Top pages last month</option>
					<option value="thisweek" '.($what=='thisweek'?'selected="selected"':'').' >Top pages this week</option>
					<option value="thismonth" '.($what=='thismonth'?'selected="selected"':'').' >Top pages this month</option>
					<option value="lastyear" '.($what=='lastyear'?'selected="selected"':'').' >Top pages last year</option>
					<option value="thisyear" '.($what=='thisyear'?'selected="selected"':'').' >Top pages this year</option>
				</select>
			</label>

			<label style="line-height: 35px; display: block;">
				' . __('How to trim titles:') . '<br/>
				<select name="sz_google_analytics_howtrim" id="sz_google_analytics_howtrim">
					<option value="regexp" '.($howtrim=='regexp'?'selected="selected"':'').' >Regular expression</option>
					<option value="replace" '.($howtrim=='replace'?'selected="selected"':'').' >Simple trim</option>
				</select>
			</label>

			<label style="line-height: 35px; display: block;" for="sz_google_analytics_trim">
				' . __('String to trim off the titles:') . '<br/>
				<input style="width: 200px;" id="sz_google_analytics_trim" name="sz_google_analytics_trim" type="text" value="'.$trim.'" />
			</label>

			<label style="line-height: 35px; display: block;" for="sz_google_analytics_onclick">
				' . __('On-click event (enter your GA trackEvent here):') . '<br/>
				<input style="width: 200px;" id="sz_google_analytics_onclick" name="sz_google_analytics_onclick" type="text" value="'.$onclick.'" />
			</label>

			<label style="line-height: 35px; display: block;" for="sz_google_analytics_nofollow">
			<input type="checkbox" id="sz_google_analytics_nofollow" '.($nofollow==1?'checked="checked"':'').' name="sz_google_analytics_nofollow" type="text" value="1" />
				' . __('Nofollow on links') . '
			</label>

			<label style="line-height: 35px; display: block;" for="sz_google_analytics_ignore">
				' . __('Posts &amp; pages to ignore (enter ID separated by a comma):') . '<br/>
				<input style="width: 200px;" id="sz_google_analytics_ignore" name="sz_google_analytics_ignore" type="text" value="'.$ignore.'" />
			</label>

			<input type="hidden" id="sz_google_analytics_widget_submit" name="sz_google_analytics_widget_submit" value="1" />
		';		
		/*
			<label style="line-height: 35px; display: block;">
				' . __('What to display:') . '<br/>
				<select name="sz_google_analytics_what" id="sz_google_analytics_what">
					<option value="posts" '.($what=='posts'?'selected="selected"':'').' >Only posts</option>
					<option value="pages" '.($what=='pages'?'selected="selected"':'').' >Only pages</option>
					<option value="both" '.($what=='both'?'selected="selected"':'').'>Posts &amp; Pages</option>
				</select>
			</label>

		*/
	}
	
	function sz_google_analytics_plugin_styles () {
		$plugin_url = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
		$css = $plugin_url . 'styles.css';
		
		wp_register_style('sz_google_analytics_plugin_styles', $css);
		wp_enqueue_style( 'sz_google_analytics_plugin_styles');
	}
	
	function sz_google_analytics_admin_menu(){
	   add_management_page('SZ Google Analytics Settings', 'SZ Google Analytics', 8,__FILE__,'sz_google_analytics_settings');
	}

	add_action('admin_menu', 'sz_google_analytics_admin_menu');

	register_sidebar_widget(array('SZ GA Top Pages Widget', 'widgets'), 'sz_google_analytics_popular_posts_widget');
	register_widget_control(array('SZ GA Top Pages Widget', 'widgets'), 'sz_google_analytics_popular_posts_widget_control', 350, 150);
	add_action('wp_print_styles', 'sz_google_analytics_plugin_styles');

}

add_action('plugins_loaded', 'sz_google_analytics_widget_init');


?>