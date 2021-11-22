<?php
class controllers{
	static $module;
	static $template;
	
	static function set_index_header(){
		$app=&aw2_library::get_array_ref('app');
		if(!isset($app['collection']['config'])) return false;
		
		$arr=aw2_library::get_module($app['collection']['config'],'settings');
		if(!$arr) return false;
		
		aw2_library::module_run($app['collection']['config'],'settings');
		$no_index = aw2_library::get_post_meta($arr['id'],'no_index');
		
		if($no_index !== 'yes')  return false;
		
		header("X-Robots-Tag: noindex", true);
		
	}
	
	static function set_cache_header($cache){
		
		// skip cache false
		//logged in user
		// app enables cache
		$c=&aw2_library::get_array_ref('cache');
		
		if($cache==='yes' && $c['enable']==='yes'){
			header("Cache-Control: max-age=31536000, public");
			header("Pragma: public");
		}	
		else{
			header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.		
			header("Pragma: no-cache"); // HTTP 1.0.
			header("Expires: 0"); // Proxies.
		}
	}
	
	static function resolve_route($pieces,$query){

		$ajax=false;
		$app=&aw2_library::get_array_ref('app');
				
		$app['active']=array('controller'=>'','collection'=>'','module'=>'','template'=>'');
		
		$o=new stdClass();
		$o->pieces=$pieces;
		
		if(empty($o->pieces))
			$o->pieces=array('home');
		
		$controller = $o->pieces[0];	
		if($controller == "ajax"){
			array_shift($o->pieces);
			$controller = $o->pieces[0]; 
			$ajax = true;
		}
	
		if(is_callable(array('controllers', 'controller_'.$controller))){
			array_shift($o->pieces);
			
			$app['active']['controller'] = $controller;
			$app['active']['collection'] = $app['collection']['modules'];
			
			call_user_func(array('controllers', 'controller_'.$controller),$o, $query);
		}
		
		if($ajax != true){
			self::controller_pages($o, $query);
			self::controller_posts($o, $query);
			self::controller_taxonomy($o, $query);
		}

		self::controller_modules($o);
		
		self:: controller_404($o);
		
	}
	
	static function controller_css($o){
		self::$module=array_shift($o->pieces);
		$app=&aw2_library::get_array_ref('app');
		self::module_parts();
		self::set_qs($o);
		$app['active']['module'] = self::$module;
		$app['active']['template'] = self::$template;
		
		$result=aw2_library::module_run($app['active']['collection'],self::$module,self::$template);


		header("Content-type: text/css");
		$c=&aw2_library::get_array_ref('cache');
		$c['enable']='yes';
		self::set_cache_header('yes');
		header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 60*24*365))); // 1 year
		echo $result;
		aw2_library::cleanup();
		exit();	
	}	
	
	static function controller_js($o){	
		self::$module=array_shift($o->pieces);

		$app=&aw2_library::get_array_ref('app');
		self::module_parts();
		self::set_qs($o);
		$app['active']['module'] = self::$module;
		$app['active']['template'] = self::$template;
		
		$result=aw2_library::module_run($app['active']['collection'],self::$module,self::$template);
		
		header("Content-type: application/javascript");
		header("Service-Worker-Allowed: /");
		$c=&aw2_library::get_array_ref('cache');
		$c['enable']='yes';
		self::set_cache_header('yes');
		header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 60*24*365))); // 1 year
		echo $result;
		aw2_library::cleanup();
		exit();	
	}
	
	static function controller_file($o){
		self::$module=array_shift($o->pieces);
		$app=&aw2_library::get_array_ref('app');
		self::module_parts();
		self::set_qs($o);
		$app['active']['module'] = self::$module;
		$app['active']['template'] = self::$template;
		
		$filename=$_REQUEST['filename'];
		$file_extension=explode('.',$filename);
		$extension=end($file_extension);
		
		$folder=aw2_library::get('realpath.app_folder');
		$path=$folder . $filename;
		
		switch ($extension) {
			case 'excel':
				header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');	
				break;				
			case 'xls':
				header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');	
				break;
			case 'xlsx':
				header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');	
				break;
			case 'pdf':
				header('Content-Type: application/pdf');	
				break;
		}
		
		header('Content-Disposition: attachment;filename="' . $filename.'"');
		self::set_cache_header('no');
		self::set_index_header();
		
		$result=file_get_contents($path);	
		echo $result;
		aw2_library::cleanup();
		exit();	
	}
	
	static function controller_fileviewer($o){
		self::$module=array_shift($o->pieces);
		$app=&aw2_library::get_array_ref('app');
		self::module_parts();
		self::set_qs($o);
		$app['active']['module'] = self::$module;
		$app['active']['template'] = self::$template;
		
		$filename=$_REQUEST['filename'];
		$file_extension=explode('.',$filename);
		$extension=end($file_extension);
		
		$folder=aw2_library::get('realpath.app_folder');
		$path=$folder . $filename;
	
		switch ($extension) {
			case 'excel':
				header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');	
				break;				
			case 'xls':
				header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');	
				break;
			case 'xlsx':
				header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');	
				break;
			case 'pdf':
				header('Content-Type: application/pdf');	
				break;
			default:
				header('Content-Type: '.mime_content_type($filename));
				break;	
		}			
		
		header("Cache-Control: max-age=2792000,public");
		header("Pragma: public");
		
		$result=file_get_contents($path);	
		echo $result;
		exit();	
	}

	static function controller_excel($o){
		self::$module=array_shift($o->pieces);
		$app=&aw2_library::get_array_ref('app');
		self::module_parts();
		self::set_qs($o);
		$app['active']['module'] = self::$module;
		$app['active']['template'] = self::$template;
		
		$filename=self::$module;	
		$folder=aw2_library::get('realpath.app_folder');
		$path=$folder . $filename;

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="' . $filename.'"');
		
		self::set_cache_header('no');
		self::set_index_header();
		
		$result=file_get_contents($path);	
		echo $result;
		exit();	
	}
	
	static function controller_z($o){
		//not cached since only admin has rights
		if(!current_user_can("develop_for_awesomeui")) exit;
		
		self::$module='';	
		if(count($o->pieces)==1 ){
			self::$module=array_shift($o->pieces);	
		}
		
		$app=&aw2_library::get_array_ref('app');
		
		if(empty(self::$module) ){
			//show list of modules
			$args=array(
				'post_type' => $app['collection']['modules']['post_type'],
				'post_status'=>'publish',
				'posts_per_page'=>500,
				'no_found_rows' => true, // counts posts, remove if pagination required
				'update_post_term_cache' => false, // grabs terms, remove if terms required (category, tag...)
				'update_post_meta_cache' => false, // grabs post meta, remove if post meta required	
				'orderby'=>'title',
				'order'=>'ASC'
			);

			self::set_cache_header('no');
			self::set_index_header();
			
			$results = new WP_Query( $args );
			$my_posts=$results->posts;

			foreach ($my_posts as $obj){
				echo('<a target=_blank href="' . site_url("wp-admin/post.php?post=" . $obj->ID  . "&action=edit") .'">' . $obj->post_title . '(' . $obj->ID . ')</a>' . '<br>');
			}
				echo('<br><a target=_blank href="' . site_url("wp-admin/post-new.php?post_type=" . $app['active']['collection']['post_type']) .'">Add New</a><br>');

		
		} else {
			aw2_library::get_post_from_slug(self::$module,$app['active']['collection']['post_type'],$post);
			header("Location: " . site_url("wp-admin/post.php?post=" . $post->ID  . "&action=edit"));
		}	
		aw2_library::cleanup();		
		exit();	
	}
	static function controller_s($o){
		global $wpdb;
		
		if(!current_user_can("develop_for_awesomeui")) return;
		
		self::$module=array_shift($o->pieces);	
		$app=&aw2_library::get_array_ref('app');
		
		$post_type=$app['active']['collection']['post_type'];
		echo '<h3>Searching for:' . urldecode(self::$module) . '</h3>';
		$sql="Select * from  ".$wpdb->posts."  where post_status='publish' and post_content like '%" . urldecode(self::$module) . "%' and post_type='" . $post_type . "'";
		global $wpdb;
		$results = $wpdb->get_results($sql,ARRAY_A);
		foreach ($results as $result){
			echo('<a target=_blank href="' . site_url("wp-admin/post.php?post=" . $result['ID']  . "&action=edit") .'">' . $result['post_title'] . '(' . $result['ID'] . ')</a>' . '<br>');
		}
		aw2_library::cleanup();		
		exit();	
	}	
	
	static function controller_search($o){
		self::$module=array_shift($o->pieces);
		$pieces=explode('.',self::$module);
		self::set_qs($o);
		
		$token=$pieces[0];
		$nonce=$pieces[1];
		
		//verify that nonce is valid
		if(wp_create_nonce($token)!=$nonce){
			echo 'Error E1:The Data Submitted is not valid. Check with Administrator';
			exit();		
		}
		$collection=aw2_library::get('services.search_service');
		echo aw2_library::module_run($collection,'search-submit',null,null,["ticket"=>self::$module]);
		aw2_library::cleanup();
		exit();	
	}
	
	static function controller_callback($o){
		self::$module=array_shift($o->pieces);
		$pieces=explode('.',self::$module);
		self::set_qs($o);
		
		$token=$pieces[0];
		$nonce=$pieces[1];
		
		//verify that nonce is valid
		if(wp_create_nonce($token)!=$nonce){
			echo 'Error E1:The Data Submitted is not valid. Check with Administrator';
			exit();		
		}

		$json=\aw2_library::get_option($token);
		if(empty($json)){
			echo 'Error E2:The Data Submitted is not valid. Check with Administrator';
			exit();		
		}				
		echo aw2_library::call_api($json);
		aw2_library::cleanup();
		exit();	
	}
	
	static function controller_csv_download($o){

		$csv_ticket=array_shift($o->pieces);
		self::set_qs($o);
		
		$filename=$_REQUEST['filename'];
		
		header("Content-type: application/csv");
		header('Content-Disposition: attachment;filename="' . $filename);
		
		self::set_cache_header('no');

		
		$redis = aw2_library::redis_connect(REDIS_DATABASE_SESSION_CACHE);
		
		if($redis->exists($csv_ticket)){
			$result = $redis->zRange($csv_ticket, 0, -1);
			$output=implode('',$result);
			echo $output;
		}
		aw2_library::cleanup();
		exit();	
	}
	
	static function controller_send_mail($o){

		$csv_ticket=array_shift($o->pieces);
		self::set_qs($o);
		
		$filename=$_REQUEST['filename'];
		
		header("Content-type: application/csv");
		header('Content-Disposition: attachment;filename="' . $filename);
		
		self::set_cache_header('no');

		
		$redis = aw2_library::redis_connect(REDIS_DATABASE_SESSION_CACHE);
		
		if($redis->exists($csv_ticket)){
			$result = $redis->zRange($csv_ticket, 0, -1);
			$output=implode('',$result);
			echo $output;
		}
		aw2_library::cleanup();
		exit();	
	}
	
	static function controller_report_csv($o){

		$csv_ticket=array_shift($o->pieces);
		self::set_qs($o);
		
		header("Content-type: text/csv");
		self::set_cache_header('no');
		header('Content-Disposition: attachment;filename="' . $csv_ticket . '.csv');		

		$sql=\aw2\session_ticket\get(["main"=>$csv_ticket,"field"=>'sql'],null,null);
		if(empty($sql)){
			echo 'Ticket is invalid: ' . $csv_ticket;
			exit();			
		}
		
		$redis = aw2_library::redis_connect(REDIS_DATABASE_SESSION_CACHE);
		
		$conn = new \mysqli(DB_HOST,DB_USER , DB_PASSWORD, DB_NAME);
			if(mysqli_multi_query($conn,$sql)){
					do{
						if ($result=mysqli_store_result($conn)) {

							$buffer = fopen('php://memory','w');
							
							$first_row=\aw2\session_ticket\get(["main"=>$csv_ticket,"field"=>'first_row'],null,null);
							if($first_row){
								$data = trim($first_row) . PHP_EOL;
								fwrite($buffer, $data );
							}
						
							for($i = 0; $row = mysqli_fetch_assoc($result); $i++){
									fputcsv($buffer, $row);
							}
							rewind($buffer);
							$csv = stream_get_contents($buffer);
							echo $csv;
			
						}
						} while(mysqli_more_results($conn) && mysqli_next_result($conn));
			}	
		exit();	
	}		

	static function controller_report_raw($o){

		$csv_ticket=array_shift($o->pieces);
		
		$sql=\aw2\session_ticket\get(["main"=>$csv_ticket,"field"=>'sql'],null,null);
		
		if(empty($sql)){
			echo 'Ticket is invalid: ' . $csv_ticket;
			exit();			
		}
		
		$redis = aw2_library::redis_connect(REDIS_DATABASE_SESSION_CACHE);
		
		$conn = new \mysqli(DB_HOST,DB_USER , DB_PASSWORD, DB_NAME);
		
		if(mysqli_multi_query($conn,$sql)){
			echo "<table border='1' cellpadding='0' cellspacing='0'>";
			do{
				if ($result=mysqli_store_result($conn)) {

					$first_row=\aw2\session_ticket\get(["main"=>$csv_ticket,"field"=>'first_row'],null,null);
					if($first_row){
						$th_data = explode(",",$first_row);
						foreach($th_data as $th){
							echo "<th align='left'>".str_replace('"',"",$th)."</th>";
						}
					}
					
					for($i = 0; $row = mysqli_fetch_assoc($result); $i++){
						echo "<tr>";
						foreach($row as $td){
							echo "<td>".$td."</td>";
						}
						echo "</tr>";
					}
	
				}
			} while(mysqli_more_results($conn) && mysqli_next_result($conn));
			echo "</table>";
		}
		
		exit();	
	}

	
	static function controller_pages($o, $query){
		if(empty($o->pieces))return;

		if(AWESOME_DEBUG) \aw2\debug\flow(['main'=>'Before running Page/Module']);			
 
	
		$slug= $o->pieces[0];
		
		$app=&aw2_library::get_array_ref('app');
		
	if(isset($app['settings']['enable_cache'])){
		self::set_cache_header($app['settings']['enable_cache']);
	}
	else	
			self::set_cache_header('no'); // HTTP 1.1.
	
	self::set_index_header();
	
			
		if(isset($app['collection']['pages'])){
			if(aw2_library::module_exists_in_collection($app['collection']['pages'],$slug)){
				array_shift($o->pieces);
				self::set_qs($o);
				$app['active']['collection'] = $app['collection']['pages'];
				$app['active']['module'] = $slug;
				$app['active']['controller'] = 'page';
				
				$output = self::run_layout($app, 'pages', $slug,$query);
			
				if($output !== false){
					echo $output; 
					if(AWESOME_DEBUG) \aw2\debug\flow(['main'=>'After running Page']);			
					aw2_library::cleanup();
					exit();
				}
				
				
				return;
			}
		}
	
		if(isset($app['collection']['modules'])){
		
			if(aw2_library::module_exists_in_collection($app['collection']['modules'],$slug)){

				array_shift($o->pieces);
				self::set_qs($o);
				
				$app['active']['collection'] = $app['collection']['modules'];
				$app['active']['module'] = $slug;
				$app['active']['controller'] = 'module';
				
				//$timeConsumed = round(microtime(true) - $GLOBALS['curTime'],3)*1000; 
				//echo '/*' .  '::before layout:' .$timeConsumed . '*/';
				
				$output = self::run_layout($app, 'modules', $slug,$query);
				if($output !== false){
					echo $output;
					if(AWESOME_DEBUG) \aw2\debug\flow(['main'=>'After running Module']);		

				//$timeConsumed = round(microtime(true) - $GLOBALS['curTime'],3)*1000; 
				//echo '/*' .  '::before exit:' .$timeConsumed . '*/';
					aw2_library::cleanup();
					exit();
				}
				
				return;
				
			}
		}	
		
		
		return;
	}
	
	static function controller_modules($o){ 

		if(empty($o->pieces))return;

		
		self::set_cache_header('no'); // HTTP 1.1.
		self::set_index_header();
		
		$app=&aw2_library::get_array_ref('app');
		self::$module= $o->pieces[0];
		self::module_parts();
		
		if(aw2_library::module_exists_in_collection($app['collection']['modules'],self::$module)){
			array_shift($o->pieces);

			$app['active']['collection'] = $app['collection']['modules'];
			$app['active']['controller'] = 'modules';
			
			self::set_qs($o);
			$app['active']['module'] = self::$module;
			$app['active']['template'] = self::$template;

			//$timeConsumed = round(microtime(true) - $GLOBALS['curTime'],3)*1000; 
			//echo '/*' .  '::before module:' .$timeConsumed . '*/';			
			$result=aw2_library::module_run($app['active']['collection'],self::$module,self::$template);

			//$timeConsumed = round(microtime(true) - $GLOBALS['curTime'],3)*1000; 
			//echo '/*' .  '::after module:' .$timeConsumed . '*/';				
			echo $result;
			//render debug bar if needs to be rendered	
			if(AWESOME_DEBUG) echo \aw2\debugbar\ajax_render([]);
			
			aw2_library::cleanup();
			exit();	
		}
	}

	static function controller_t($o){ 
		if(empty($o->pieces))return;

		self::set_cache_header('no'); // HTTP 1.1.
		
		$app=&aw2_library::get_array_ref('app');
		$ticket=array_shift($o->pieces);
		$hash=\aw2\session_ticket\get(["main"=>$ticket],null,null);
		if(!$hash || !$hash['ticket_activity']){
			echo 'Ticket is invalid: ' . $ticket;
			exit();			
		}
		$ticket_activity=json_decode($hash['ticket_activity'],true);
		
		
		self::set_qs($o);
		$app['active']['controller'] = 'ticket';
		$app['active']['ticket'] = $ticket;
		
		if(isset($ticket_activity['service'])){
			//$hash['main']=$ticket_activity['service'];
			$hash['service']=$ticket_activity['service'];
			$result=\aw2\service\run($hash,null,[]);
			echo $result;
			//render debug bar if needs to be rendered	
			if(AWESOME_DEBUG) echo \aw2\debugbar\ajax_render([]);		
			aw2_library::cleanup();
			exit();	
		}
		
		if(!isset($ticket_activity['module'])){
			echo 'Ticket is invalid for module: ' . $ticket;
			exit();			
		}		
		
		
		self::$module= $ticket_activity['module'];
		self::module_parts();

		if(isset($ticket_activity['collection']))
			$app['active']['collection'] = $ticket_activity['collection'];
		else
			$app['active']['collection'] = $app['collection']['modules'];
			
		$app['active']['module'] = self::$module;
		$app['active']['template'] = self::$template;
		
		//$timeConsumed = round(microtime(true) - $GLOBALS['curTime'],3)*1000; 
		//echo '/*' .  '::before layout:' .$timeConsumed . '*/';
		

		$result=aw2_library::module_run($app['active']['collection'],self::$module,self::$template,null,$hash);

		//$timeConsumed = round(microtime(true) - $GLOBALS['curTime'],3)*1000; 
		//echo '/*' .  '::before exit:' .$timeConsumed . '*/';

		echo $result;
		//render debug bar if needs to be rendered	
		if(AWESOME_DEBUG) echo \aw2\debugbar\ajax_render([]);
		
		aw2_library::cleanup();		
		exit();	
	}

	static function controller_ts($o){ 
		if(empty($o->pieces))return;

		self::set_cache_header('no'); // HTTP 1.1.
		
		$app=&aw2_library::get_array_ref('app');
		$ticket=array_shift($o->pieces);
		

		$hash=\aw2\session_ticket\get(["main"=>$ticket],null,null);
		if(!$hash || !$hash['payload']){
			echo 'Ticket is invalid: ' . $ticket;
			exit();			
		}
		$payload=json_decode($hash['payload'],true);
		//\util::var_dump($payload);
		self::set_qs($o);
		$app['active']['controller'] = 'ticket';
		$app['active']['ticket'] = $ticket;
		$result=array();
						
		foreach ($payload as $one) {
			$arr=isset($one['data'])?$one['data']:array();
			$arr['service']=$one['service'];
			$result[]=\aw2\service\run($arr,null,[]);
		}
		echo implode('',$result);
		//render debug bar if needs to be rendered	
		if(AWESOME_DEBUG) echo \aw2\debugbar\ajax_render([]);		
		aw2_library::cleanup();
		exit();	
	}	
	
	static function controller_posts($o, $query){
	
		if(empty($o->pieces))return;
		
		$app=&aw2_library::get_array_ref('app');

		if(isset($app['settings']['enable_cache'])){
			self::set_cache_header($app['settings']['enable_cache']);
		}
		else	
				self::set_cache_header('no'); // HTTP 1.1.
		
		self::set_index_header();
		
		if(!isset($app['collection']['posts'])) return;
		
		$slug= $o->pieces[0];
	
		$post_type = $app['collection']['posts']['post_type'];
			
			
		if(!aw2_library::module_exists_in_collection($app['collection']['posts'],$slug)) return;
			
		array_shift($o->pieces);
		self::set_qs($o);
		$app['active']['collection'] = $app['collection']['posts'];
		$app['active']['module'] = $slug; // this is kept to keep this workable
		$app['active']['controller'] = 'posts';	
		$output = false;
		
		if(isset($app['configs'])){
			$layout='';
			$app_config = $app['configs'];
			
			if(isset($app_config['layout'])){
				$layout='layout';
			}
			if(isset($app_config['posts-single-layout'])){
				$layout='posts-single-layout';
			}
			
			if(!empty($layout)){
				aw2_library::set('current_post',$post);
				$output = aw2_library::module_run($app['collection']['config'],$layout,null,null);
			}
		}
		
		if($output !== false){
			echo $output;
			aw2_library::cleanup();
			exit();
		}
		
		$query->query_vars[$post_type]=$slug;
		$query->query_vars['post_type']=$post_type;
		$query->query_vars['name']=$slug;
		unset($query->query_vars['error']);

		return;
	}
	
	static function controller_taxonomy($o, $query){
		if(empty($o->pieces))return;
		
		$app=&aw2_library::get_array_ref('app');

		if(isset($app['settings']['enable_cache'])){
			self::set_cache_header($app['settings']['enable_cache']);
		}
		else	
				self::set_cache_header('no'); // HTTP 1.1.
		
		self::set_index_header();
		
		if(!isset($app['settings']['default_taxonomy'])) return;
		
		$slug= $o->pieces[0];
		$taxonomy	= $app['settings']['default_taxonomy'];
		
		$post_type='';
		if( isset($app['collection']['posts']))
			$post_type	= $app['collection']['posts']['post_type'];
		
	
		if(empty($taxonomy) || !term_exists( $slug, $taxonomy )) return;
			
		array_shift($o->pieces);
		self::set_qs($o);
		//taxonomy archive will be handled by archive.php == archive-content-layout;		
		$query->query_vars[$taxonomy]=$slug;
		$query->query_vars['post_type']=$post_type;
		unset($query->query_vars['attachment']);
		unset($query->query_vars['name']);
		unset($query->query_vars[$app['collection']['pages']['post_type']]);

		return;
	}
	
	static function controller_404($o){
		if(empty($o->pieces))return;
		
		$app=&aw2_library::get_array_ref('app');
		
		if(isset($app['settings']['enable_cache'])){
			self::set_cache_header($app['settings']['enable_cache']);
		}
		else	
				self::set_cache_header('no'); // HTTP 1.1.
		self::set_index_header();
		
		$post_type = $app['collection']['modules']['post_type'];
		
		if(isset($app['settings']['unhandled_module'])){
			self::$module=$app['settings']['unhandled_module'];

			$app['active']['collection'] = $app['collection']['modules'];
			$app['active']['controller'] = 'unhandled_module';
		
			self::module_parts();
			self::set_qs($o);
			
			$app['active']['module'] = self::$module;
			$app['active']['template'] = self::$template;
			
			$result=aw2_library::module_run($app['active']['collection'],self::$module,self::$template);

			echo $result;
			aw2_library::cleanup();
			exit();	
		}
		
		if(aw2_library::module_exists_in_collection($app['collection']['modules'],'404-page')){
			array_shift($o->pieces);
			$this->action='404';
			
			$query->query_vars['post_type']=$post_type;
			$query->query_vars['pagename']='404-page';
			return;
		}	
	}
	
	
	static function controller_mreports_csv($o){
		
		$app=&aw2_library::get_array_ref('app');
		$ticket=\aw2\request2\get(['main'=>'ticket_id']);
		
		header("Content-type: text/csv");
		self::set_cache_header('no');
		header('Content-Disposition: attachment;filename="' . $ticket . '.csv');		


		$hash=\aw2\session_ticket\get(["main"=>$ticket],null,null);
		if(!$hash || !$hash['ticket_activity']){
			echo 'Ticket is invalid: ' . $ticket;
			exit();			
		}
		$ticket_activity=json_decode($hash['ticket_activity'],true);
		
		
		self::set_qs($o);
		$app['active']['controller'] = 'ticket';
		$app['active']['ticket'] = $ticket;
		$app['ticket']['data'] = $hash;
		
		if(isset($ticket_activity['service'])){
			$hash['service']=$ticket_activity['service'];
			$result=\aw2\service\run($hash,null,[]);
			$buffer = fopen('php://memory','w');
			
			$first_row=isset($app['first_row']) ? $app['first_row'] : null;
			if($first_row){
				$data = trim($first_row) . PHP_EOL;
				fwrite($buffer, $data );
			}
		
			foreach($app['result'] as $row){
					fputcsv($buffer, $row);
			}
			rewind($buffer);
			$csv = stream_get_contents($buffer);
			echo $csv;
			exit();	
		}
		exit();	
	}			
	
	static function run_layout($app, $collection, $slug,$query){
		
		if(isset($app['collection']['config'])){
			$layout='';
			$app_config=$app['collection']['config'];
			
			$exists=aw2_library::module_exists_in_collection($app_config,'layout');
			if($exists)$layout='layout';

			$exists=aw2_library::module_exists_in_collection($app_config,$collection.'-layout');
			if($exists)$layout=$collection.'-layout';

			if(!empty($layout)){
				return aw2_library::module_run($app_config,$layout,null,null);
			}
		}
				
		if($collection == 'modules'){
			return 	$result=aw2_library::module_run($app['active']['collection'],$slug,'');

		}
		
		/* if(!isset($app['active']['collection']['post_type']) && aw2_library::post_exists('layout',AWESOME_CORE_POST_TYPE)){
				return aw2_library::module_run(['post_type'=>AWESOME_CORE_POST_TYPE],'layout',null,null);
		}	 */
		
		// well none of the layout options exists so hand it over to page.php
		 
		unset($query->query_vars['name']);
		unset($query->query_vars['attachment']);
		unset($query->query_vars['post_type']);
		unset($query->query_vars['page']);
		unset($query->query_vars['error']);
		unset($query->query_vars[$app['active']['collection']['post_type']]);
		
		$query->query_vars['post_type']=$app['active']['collection']['post_type'];
		$query->query_vars['pagename']=$slug;
		
		//exit();
		return false;
	}
	
	static function set_qs($o){
		$qs=&aw2_library::get_array_ref('qs');
		$i=0;
		foreach ($o->pieces as $value){
			$qs[$i]=\aw2\clean\safe(['main'=>$value]);
			$i++;
			/* $pos = strpos($value, '$$');
			if ($pos === false) {
				$qs[$i]=\aw2\clean\safe(['main'=>$value]);
				$i++;
			} else {
				$arr=explode('$$',$value);
				$qs[$arr[0]]=\aw2\clean\safe(['main'=>$arr[1]]);
			} */
			array_shift($o->pieces);
		}
	}
	
	static function module_parts(){
		$t=strpos(self::$module,'.');
		if($t===false){
			self::$template='';
			return;	
		}
		$parts=explode ('.' , self::$module); 
		
		self::$module=$parts[0];
		array_shift($parts);
		self::$template=implode('.',$parts);
	}
	
	/// New function by Sam on 9th august related to Ag Grid 
	static function controller_report_grid($o){

		$grid_ticket=array_shift($o->pieces);
		
		$sql=\aw2\session_ticket\get(["main"=>$grid_ticket,"field"=>'sql'],null,null);
		
		if(empty($sql)){
			echo 'Ticket is invalid: ' . $grid_ticket;
			exit();			
		}
		
		$redis = aw2_library::redis_connect(REDIS_DATABASE_SESSION_CACHE);
		
		$conn = new \mysqli(DB_HOST,DB_USER , DB_PASSWORD, DB_NAME);

		$report_header_name= "";
		$header= "";
		$rows=array();
		
		if(mysqli_multi_query($conn,$sql)){


			do{
				if ($result=mysqli_store_result($conn)) {

					$first_row=\aw2\session_ticket\get(["main"=>$grid_ticket,"field"=>'first_row'],null,null);
					$header=\aw2\session_ticket\get(["main"=>$grid_ticket,"field"=>'custom_aggrid_header'],null,null);
					$report_header_name=\aw2\session_ticket\get(["main"=>$grid_ticket,"field"=>'header_name'],null,null);
					
					for($i = 0; $row = mysqli_fetch_assoc($result); $i++){

						$rows[] = $row;

				
					}
	
				}
			} while(mysqli_more_results($conn) && mysqli_next_result($conn));


			if(is_array($rows) && count($rows))
			{
				$total_records = count($rows);
			}

			
			
			//$json_decoded_header = json_decode($header);

			$json_decoded_header = json_decode($header,true);
			// print "<pre>";
			// print_r($json_decoded_header);
			$temp_arr = array();

			/* Few variables we have to change here as Ag Grid internally needs Capital case for ex. headerName, enableValue, however our awesome code is 
			making it all lower case of all the array keys hence we are using below code to change the keys in ag grid expected format */

			if(is_array($json_decoded_header) && count($json_decoded_header))
			{
				foreach($json_decoded_header as $key_outer=>$array1)
				{
					if(is_array($array1) && count($array1))
					{
						foreach($array1 as $key_inner=>$array2)
						{
							if($key_inner=="header_name")
							{
								$temp_arr[$key_outer]['headerName'] = $json_decoded_header[$key_outer]['header_name'];
							}
							else if($key_inner=="enable_value")
							{
								$temp_arr[$key_outer]['enableValue'] = (bool)$json_decoded_header[$key_outer]['enable_value'];
							}
							else if($key_inner=="enable_row_group")
							{
								$temp_arr[$key_outer]['enableRowGroup'] = (bool) $json_decoded_header[$key_outer]['enable_row_group'];
							}
							else if($key_inner=="row_group")
							{
								$temp_arr[$key_outer]['rowGroup'] = (bool)$json_decoded_header[$key_outer]['row_group'];
							}
							else if($key_inner=="hide")
							{
								$temp_arr[$key_outer]['hide'] = (bool)$json_decoded_header[$key_outer]['hide'];
							}
							else if($key_inner=="agg_func")
							{
								$temp_arr[$key_outer]['aggFunc'] = $json_decoded_header[$key_outer]['agg_func'];
							}
							else if($key_inner=="enable_pivot")
							{
								$temp_arr[$key_outer]['enablePivot'] = (bool)$json_decoded_header[$key_outer]['enable_pivot'];
							}
							else{
								$temp_arr[$key_outer][$key_inner] = $json_decoded_header[$key_outer][$key_inner];
							}

							$temp_arr[$key_outer]['filter'] = 'agSetColumnFilter';
							// echo "<br>=> key inner ".$key_inner;
							// echo "<br>=> array2 ".$array2;
						}

						if(isset($json_decoded_header[$key_outer]['to_int']) && $json_decoded_header[$key_outer]['to_int'] == 'yes')
						{
							for($counter = 0; $counter<$total_records;$counter++)
							{
								// print_r($temp_arr[$key_outer]);

								$key_name = $temp_arr[$key_outer]['field'];
								
								$rows[$counter][$key_name] = (int) $rows[$counter][$key_name]; 
							}
						}						

						
					}
					
				}
	
			}

			
			$columns_json = json_encode($temp_arr);
			
			

			// print "<pre>";
			// print_r($rows);
			// exit;

			if(isset($rows) && is_array($rows) && count($rows))
			{
				$rows  = json_encode($rows);
			}
			else
			{
				echo "<div style='text-align:center;'><h1>$report_header_name </h1><br><br><h3>No data to display for this selection</h3></div>";
				exit;
			}
			echo "
			<script>
				function onBtExport()
				{
					var params = {
					};					
					gridOptions.api.exportDataAsCsv(params);
				}

			</script>
			<label style='margin-left: 20px;'>
            	
			</label>
			
			<div style='text-align:center;'>
			<button onclick='onBtExport()'>Export to CSV</button><h1>$report_header_name </h1>
			</div>
			";

			echo '<div id="grid-wrapper" style="padding: 1rem; padding-top: 0; overflow:hidden;">';
			echo '<div id="myGrid" style="height: 85%; overflow:hidden;" class="ag-theme-balham" >';
			echo "</div></div>";			


			
			echo "
			<script src='https://unpkg.com/ag-grid-enterprise@21.0.1/dist/ag-grid-enterprise.min.js' ></script>
				<script >
				
				
			var columnDefs = $columns_json ;
			var gridOptions = {
			   defaultColDef: {
				   sortable: true,
				   resizable: true
			   },
			   // set rowData to null or undefined to show loading panel by default
			   
			   rowData: $rows,
			   columnDefs: columnDefs,
			   popupParent: document.body,
			   rowGroupPanelShow: 'always',
			   animateRows: true,
			   sideBar: 'columns',
			   enableCharts: true,
			   pivotMode: true, 
			   groupIncludeFooter: true,
               groupIncludeTotalFooter: true,
    		   pivotColumnGroupTotals: 'before',
			   enableRangeSelection: true,
			   enableRangeHandle: false,
			   enableFillHandle: false,    
			   rowSelection: 'multiple',
			   rowDeselection: true,
			   enablePivot: true,
			   filter: true,
			   sideBar: {
				toolPanels: [
					{
						id: 'columns',
						labelDefault: 'Columns',
						labelKey: 'columns',
						iconKey: 'columns',
						toolPanel: 'agColumnsToolPanel',
					},
					{
						id: 'filters',
						labelDefault: 'Filters',
						labelKey: 'filters',
						iconKey: 'filter',
						toolPanel: 'agFiltersToolPanel',
					}
				],
				defaultToolPanel: 'columns'
			}
			};

			 var gridDiv = document.querySelector('#myGrid');
			 new agGrid.Grid(gridDiv, gridOptions);		
			 
			 

		</script>
		<script type='text/javascript'>window.NREUM||(NREUM={});NREUM.info={'beacon':'bam.nr-data.net','licenseKey':'0dcebb20b3',
			'applicationID':'171679852','transactionName':'bldbMBMEDBFXAUIMWlcdbBYISgsMUgdOS0VRQg==','queueTime':0,
			'applicationTime':526,'atts':'QhBYRlseHx8=','errorBeacon':'bam.nr-data.net','agent':''}</script>
		";			
			
		}		
		exit();	
	}
}