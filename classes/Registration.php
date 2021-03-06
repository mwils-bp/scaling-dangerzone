<?php
class Registration
{
    private $db_connection = null;
    private $user_name = "";
    private $user_forename = "";
    private $user_surname = "";
    private $user_password = "";
    private $user_password_hash = "";
    public $registration_successful = false;
    public $errors = array();
    public $messages = array();

    /**
     * the function "__construct()" automatically starts whenever an object of this class is created,
     * you know, when you do "$login = new Login();"
     */
    public function __construct()
    {
        if (isset($_POST["register"])) {
            $this->registerNewUser();
        }
    }

    /**
     * handles the entire registration process. checks all error possibilities, and creates a new user in the database if
     * everything is fine
     */
    private function registerNewUser()
    {
        if (empty($_POST['user_name'])) {
            $this->errors[] = '<div style="padding:30px;"><div class="alert alert-danger">Empty Username</div></div>';
        } elseif (empty($_POST['user_password_new']) || empty($_POST['user_password_repeat'])) {
            $this->errors[] = '<div style="padding:30px;"><div class="alert alert-danger">Empty Password</div></div>';
        } elseif ($_POST['user_password_new'] !== $_POST['user_password_repeat']) {
            $this->errors[] = '<div style="padding:30px;"><div class="alert alert-danger">Passwords do not match.</div></div>';
        } elseif (strlen($_POST['user_password_new']) < 6) {
            $this->errors[] = '<div style="padding:30px;"><div class="alert alert-danger">Password has a minimum length of 6 characters</div></div>';
        } elseif (strlen($_POST['user_name']) > 64 || strlen($_POST['user_name']) < 2) {
            $this->errors[] = '<div style="padding:30px;"><div class="alert alert-danger">Username cannot be shorter than 2 or longer than 64 characters</div></div>';
        } elseif (!preg_match('/^[a-z\d]{2,64}$/i', $_POST['user_name'])) {
            $this->errors[] = '<div style="padding:30px;"><div class="alert alert-danger">Username does not fit the name scheme: only a-Z and numbers are allowed, 2 to 64 characters</div></div>';
        } elseif (empty($_POST['user_forename'])) {
            $this->errors[] = '<div style="padding:30px;"><div class="alert alert-danger">Forename cannot be empty</div></div>';
        } elseif (strlen($_POST['user_forename']) > 20) {
            $this->errors[] = '<div style="padding:30px;"><div class="alert alert-danger">Forename cannot be longer than 20 characters</div></div>';
        } elseif (!empty($_POST['user_name'])
            && strlen($_POST['user_name']) <= 64
            && strlen($_POST['user_name']) >= 2
            && preg_match('/^[a-z\d]{2,64}$/i', $_POST['user_name'])
            && !empty($_POST['user_forename'])
            && strlen($_POST['user_forename']) <= 20
            && !empty($_POST['user_password_new'])
            && !empty($_POST['user_password_repeat'])
            && ($_POST['user_password_new'] === $_POST['user_password_repeat'])
        ) {

            // TODO: the above check is redundant, but from a developer's perspective it makes clear
            // what exactly we want to reach to go into this if-block

            // creating a database connection
            $this->db_connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            // if no connection errors (= working database connection)
            if (!$this->db_connection->connect_errno) {

                // escapin' this, additionally removing everything that could be (html/javascript-) code
                $this->user_name = $this->db_connection->real_escape_string(htmlentities($_POST['user_name'], ENT_QUOTES));
                $this->user_forename = $this->db_connection->real_escape_string(htmlentities($_POST['user_forename'], ENT_QUOTES));

                $this->user_password = $_POST['user_password_new'];

                // crypt the user's password with the PHP 5.5's password_hash() function, results in a 60 character hash string
                // the PASSWORD_DEFAULT constant is defined by the PHP 5.5, or if you are using PHP 5.3/5.4, by the password hashing
                // compatibility library                
                $this->user_password_hash = password_hash($this->user_password, PASSWORD_DEFAULT);

                // check if user already exists
                $query_check_user_name = $this->db_connection->query("SELECT * FROM user WHERE user_login = '" . $this->user_name . "';");

                if ($query_check_user_name->num_rows == 1) {
                    $this->errors[] = "Sorry, that user name is already taken. Please choose another one.";
                } else {
                    // write new users data into database
                    $query_new_user_insert = $this->db_connection->query("INSERT INTO user (user_login, user_password, user_forename, user_surname) VALUES('" . $this->user_name . "', '" . $this->user_password_hash . "', '" . $this->user_forename . "', '" . $this->user_surname . "');");

                    if ($query_new_user_insert) {
                        $this->messages[] = "Your account has been created successfully. You can now log in.";
                        $this->registration_successful = true;
                    } else {
                        $this->errors[] = "Sorry, your registration failed. Please go back and try again.";
                    }
                }
            } else {
                $this->errors[] = "Sorry, no database connection.";
            }
        } else {
            $this->errors[] = "An unknown error occurred.";
        }
    }
}
