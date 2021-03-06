<?php

class users_controller extends base_controller {

	public function __construct() { #two underscores indicate this is a "magic" method
		parent::__construct(); #this calls construct on the base
		#methods included here will get called with every users method		
	}
	
	public function index() { #calls users without specifying a method
		#if user is blank, they're not logged in; redirect to login/registration page
		if(!$this->user) {
			Router::redirect("/users/login");
			
			#return will force this method to exit here so the rest of the code won't be executed and the profile view won't be displayed
			return false;
		}
		
		#they are logged in, bring them to their profile
		Router::redirect("/users/profile");
		
	}
	
	public function signup($message = NULL, $error = NULL) {
		#setup view
		$this->template->header = View::instance('v_header');
		$this->template->footer = View::instance('v_footer');
		$this->template->content = View::instance('v_users_login');
		
		#pass data to the view
		$this->template->content->error = $error;
		$this->template->content->message = $message;
		
		#render template
		echo $this->template;
	}
	
	#submits the registration form
	public function p_signup($error = NULL) {
		
		#prints what data was submitted to the page
		#print_r($_POST);
		
		#encrypts password before sending to DB
		$_POST['password'] = sha1(PASSWORD_SALT.$_POST['password']);
		
		#more data we want stored with the user
		$_POST['created'] = Time::now(); //this returns the current timestamp
		$_POST['modified'] = Time::now();
		$_POST['token'] = sha1(TOKEN_SALT.$_POST['email'].Utils::generate_random_string());
		
		#sanitize the user entered data
		$_POST = DB::instance(DB_NAME)->sanitize($_POST);
		
		#check to see if email entered matches an email in the DB
			#Build query to DB to see if there is a matching email

			$q = "SELECT email
				FROM users
				WHERE email = '".$_POST['email']."'";
			
			#Run query and store in an array
			$matches = DB::instance(DB_NAME)->select_rows($q);
		
			#If matches is empty, signup succeeds
			if(empty($matches)) {
				#put the registration data in the database
				$user_id = DB::instance(DB_NAME)->insert('users', $_POST);
			
				#setup view
				$this->template->header = View::instance('v_header');
				$this->template->footer = View::instance('v_footer');
				$this->template->content = View::instance('v_users_login');
				
				#pass data to the view
				$this->template->content->error = $error;
				$this->template->content->message = "signup-success";
				
				#render template
				echo $this->template;
			
			} else {
				#Otherwise, there is a match and signup fails
				
				#setup view
				$this->template->header = View::instance('v_header');
				$this->template->footer = View::instance('v_footer');
				$this->template->content = View::instance('v_users_login');
				
				#pass data to the view
				$this->template->content->error = $error;
				$this->template->content->message = "signup-error";
				
				#render template
				echo $this->template;
				
			}
			
	}
	
	public function login($error = NULL, $message = NULL) {
		#setup view
		$this->template->header = View::instance('v_header');
		$this->template->footer = View::instance('v_footer');
		$this->template->content = View::instance('v_users_login');
		
		#pass data to the view
		$this->template->content->error = $error;
		$this->template->content->message = $message;
		
		#render template
		echo $this->template;
	}
	
	public function p_login() {
		#hash submitted password so we can compare it against one in the DB
		$_POST['password'] = sha1(PASSWORD_SALT.$_POST['password']);
		
		#sanitize the user entered data to prevent any funny business
		$_POST = DB::instance(DB_NAME)->sanitize($_POST);
		
		#search the db for this email and password
		#retrieve the token if it's available
		$q = "SELECT token
			FROM users
			WHERE email = '".$_POST['email']."'
			AND password = '".$_POST['password']."'";
			
		$token = DB::instance(DB_NAME)->select_field($q);
		
		#if we didn't get a token back, login failed
		if(!$token) {
			#send them back to the login page w/ the error parameter
			Router::redirect("/users/login/error");
			
		#but if we did, login succeeded!
		} else {
			#store this token in a cookie
			@setcookie("token", $token, strtotime('+1 week'), '/');
			
			#send them to their profile
			Router::redirect("/users/profile");
		}
	}
	
	public function logout() {
		#generate and save a new token for next login
		$new_token = sha1(TOKEN_SALT.$this->user->email.Utils::generate_random_string());
		
		#create the data array we'll use with the update method
		#in this case, we're only updating one field, so our array only has one entry
		$data = Array("token" => $new_token);
		
		#do the update
		DB::instance(DB_NAME)->update("users", $data, "WHERE token = '".$this->user->token."'");
		
		#delete their token cookie - effectively logging them out
		setcookie("token", "", strtotime('-1 week'), '/');
		
		#send them back to the main login page w/ success parameter
		Router::redirect("/index/index");
	}
	
	
	public function profile() {
		#if user is blank, they're not logged in; redirect to login/registration page
		if(!$this->user) {
			Router::redirect("/users/login");
			
			#return will force this method to exit here so the rest of the code won't be executed and the profile view won't be displayed
			return false;
		}
		
		#they are logged in, bring them to their profile
		$this->template->header = View::instance('v_header');
		#show logged in header
		$this->template->header->welcome = View::instance('v_header_welcome');
		$this->template->footer = View::instance('v_footer');
		$this->template->content = View::instance('v_main_content');
		$this->template->content->nav = View::instance('v_nav');
		$this->template->content->tabGuts = View::instance('v_users_profile');
		$this->template->content->tabGuts->addPost = View::instance('v_posts_add');
		$this->template->content->tabGuts->myPosts = View::instance('v_posts_my_posts');
		$this->template->title = "MicroBlog - My Profile";
		
		#Builds a query to grab all posts by this user
		#Selects everything in 'posts' and select fields in 'users' (so 'created' is unambiguous)
		$q = "SELECT posts.*, users.user_id, users.first_name, users.last_name
			FROM posts
			JOIN users USING (user_id)
			WHERE user_id = ".$this->user->user_id;
			
		#Run the query, storing the results in the variable $posts
		$posts = DB::instance(DB_NAME)->select_rows($q);
		
		#If $posts is empty, user hasn't made any posts yet
		if(empty($posts)) {
			$this->template->content->tabGuts->myPosts->show_no_posts_message = TRUE;
		} else {
			$this->template->content->tabGuts->myPosts->show_no_posts_message = FALSE;
		}
		
		#Pass data to the View
		$this->template->content->tabGuts->myPosts->posts = $posts;
		
		echo $this->template;
		
		}
		
	public function settings($message = NULL) {
	
		$this->template->header = View::instance('v_header');
		#show logged in header
		$this->template->header->welcome = View::instance('v_header_welcome');
		$this->template->footer = View::instance('v_footer');
		$this->template->content = View::instance('v_main_content');
		$this->template->content->nav = View::instance('v_nav');
		$this->template->content->tabGuts = View::instance('v_users_settings');
		$this->template->title = "MicroBlog - Settings";
		
		$this->template->content->tabGuts->message = $message;
		
		echo $this->template;
	}
	
	public function p_change_password() {
		#Encrypt the new password
		$new_password = sha1(PASSWORD_SALT.$_POST['new_password']);
		
		$data = Array("password" => $new_password);
		
		#Update the DB
		DB::instance(DB_NAME)->update("users", $data, "WHERE token = '".$this->user->token."'");
		
		#Send them back with a message
		Router::redirect("/users/settings/message");
	}
	
}