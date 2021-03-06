<?php

class posts_controller extends base_controller {

	public function __construct() {
		parent::__construct();
		#Make sure user is logged in if they want to use anything in this controller
		if(!$this->user) {
			die(Router::redirect("/users/profile"));
		}
		
	}
	
	#spits out posts of users followed by this user (My Feed)
	public function index() {
	
		#Set up view
		$this->template->header = View::instance('v_header');
		#show logged in header
		$this->template->header->welcome = View::instance('v_header_welcome');
		$this->template->footer = View::instance('v_footer');
		$this->template->content = View::instance('v_main_content');
		$this->template->content->nav = View::instance('v_nav');
		$this->template->content->tabGuts = View::instance('v_posts_index');
		$this->template->title = "MicroBlog - My Feed";
		
		#Build a query of the users this user is following
		$q = "SELECT *
			FROM users_users
			WHERE user_id = ".$this->user->user_id;
			
		#Execute our query, storing the results in a variable $connections
		$connections = DB::instance(DB_NAME)->select_rows($q);
		
		#In order to query for the posts we need, we're going to need a string of user id's, separated by commas
		#To create this, loop through our connections array
		$connections_string = "";
		foreach($connections as $connection) {
			$connections_string .=$connection['user_id_followed'].",";
		}
		
		#Remove the final comma
		$connections_string = substr($connections_string, 0, -1);
		
		#If user isn't following anyone yet, prevent a SQL error
		if (empty($connections_string)) {
			$this->template->content->tabGuts->show_no_follows_message = TRUE;
		} else {
			
			$this->template->content->tabGuts->show_no_follows_message = FALSE;
			
			#Build our query to grab the posts
			#Selects everything in 'posts' and select fields in 'users' (so 'created' is unambiguous)
			$q = "SELECT posts.*, users.user_id, users.first_name, users.last_name
				FROM posts
				JOIN users USING (user_id)
				WHERE posts.user_id IN (".$connections_string.")"; #This is where we use that string of user_ids we created
		}
			
		#Run our query, store the results in the variable $posts
		$posts = DB::instance(DB_NAME)->select_rows($q);
		
		#If $posts is empty, show a message
		if(empty($posts)) {
			$this->template->content->tabGuts->show_no_posts_message = TRUE;
		} else {
		
			$this->template->content->tabGuts->show_no_posts_message = FALSE;
		}
		
		#Pass data to the view
		$this->template->content->tabGuts->posts = $posts;
		
		#Render view
		echo $this->template;
	
	}
	
	#spits out posts added by this user
	public function my_posts() {
	
		#Set up view
		$template = View::instance('v_posts_my_posts');
		
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
			$template->show_no_posts_message = TRUE;
		} else {
			$template->show_no_posts_message = FALSE;
		}
		
		#Pass data to the View
		$template->posts = $posts;
		
		#Render view
		echo $template;
	
	}
	
	public function add() {
	
		#Setup view
		#creates a new blank template just for this method
		$template = View::instance('v_posts_add');
		echo $template;
		
	}
	
	public function p_add() {
		
		#Associate this post with this user
		$_POST['user_id'] = $this->user->user_id;
		
		#Unix timestamp of when this post was created/modified
		$_POST['created']  = Time::now();
		$_POST['modified'] = Time::now();
		
		#Insert
		#Note we didn't have to sanitize any of the $_POST data because we're using the insert method which does it for us
		DB::instance(DB_NAME)->insert('posts', $_POST);
		
		#refreshes the user's profile
		Router::redirect("/users/profile");
			
	}
	
	#Deletes the post w/ id passed in as a parameter
	public function delete($post_id_deleted) {
		#Define the parameters for the query
		$table = 'posts';
		$where_condition = "WHERE post_id = ".$post_id_deleted;
				
		#Execute the query to delete the post
		DB::instance(DB_NAME)->delete($table, $where_condition);
		
		#Send the user back to their profile
		Router::redirect('/users/profile');
	}
	
	
	#spits out list of all users w/ follow/unfollow buttons
	public function users() {
	
		#Set up view
		$this->template->header = View::instance('v_header');
		#show logged in header
		$this->template->header->welcome = View::instance('v_header_welcome');
		$this->template->footer = View::instance('v_footer');
		$this->template->content = View::instance('v_main_content');
		$this->template->content->nav = View::instance('v_nav');
		$this->template->content->tabGuts = View::instance('v_posts_users');
		$this->template->title = "Microblog - All Users";
		
		#Build our query to get all the users
		$q = "SELECT *
			FROM users";
			
		#Execute the query to get all the users. Store the result array in the variable $users
		$users = DB::instance(DB_NAME)->select_rows($q);
		
		#Build our query to figure out what connections does this user already have? I.e. who are they following?
		$q = "SELECT *
			FROM users_users
			WHERE user_id = ".$this->user->user_id;
			
		#Execute this query with the select_array method
		#select_array will return our results in an array and use the "users_id_followed" filed as the index.
		#This will come in handy when we get to the view
		#Store our results (an array) in the variable $connections
		$connections = DB::instance(DB_NAME)->select_array($q, 'user_id_followed');
		
		#Pass data (users and connections) to the view
		$this->template->content->tabGuts->users = $users;
		$this->template->content->tabGuts->connections = $connections;
		
		#Render the view
		echo $this->template;
	
	}
	
	public function follow($user_id_followed) {
	
		#Prepare our data array to be inserted
		$data = Array(
			"created" => Time::now(),
			"user_id" => $this->user->user_id,
			"user_id_followed" => $user_id_followed
			);
			
		#Do the insert
		DB::instance(DB_NAME)->insert('users_users', $data);
		
		#Send them back
		Router::redirect("/posts/users");
	
	}
	
	public function unfollow($user_id_followed) {
	
		#Delete this connection
		$where_condition = 'WHERE user_id = '.$this->user->user_id.' AND user_id_followed = '.$user_id_followed;
		DB::instance(DB_NAME)->delete('users_users', $where_condition);
		
		#Send them back
		Router::redirect("/posts/users");
	
	}
	
}