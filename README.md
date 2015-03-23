# Lull <small>*#hopefullyitworks #nopromises*</small>
 a PHP API framework:
- [Easily add endpoints](#endpoints)
- [Basic user login and management](#apiexamples) right from the start
- [Logging library](#logger)
- [Database-stored session replacement library](#session) (if you want)
- Easily enable a [generic endpoint for simple tables](#generic)
- [Email templates](#emails)
- Required tables are created automatically at run-time


## Setup
1. Download the files and place them on a webserber.
2. Update config.inc.php with your site information and database connection credentials
3. Try  it out: `[api_location]/test?name=tester`

## <a name="endpoints"></a>How it works

To add an endpoint to your API, you create an endpoint Controller, and decide whether users need to be logged in to use it, or if they can access it anonymously.

Adding an endpoint is easy! Take a look at [Test.controller.php](Test.controller.php) for as an example. As you can see, it extends [Controller](lib/Controller.class.php), and abstract class with some helpful static functions. 

```PHP
Controller::error($code, $desc)
// Returns an HTTP error with the *code* passed, with *desc* as the body.

Controller::json_out($obj)
// Terminates the script after sending *obj* as JSON.

Controller::getData($verb) 
// Returns an array of passed data - the json_decoded payload for 'put' and 'get', and *$_REQUEST* otherwise.
```
##### Enabling your endpoint
Now that you have your endpoint, you enable it by  adding it to one of the controller arrays in [index.php](index.php), depending on whether you want to allow anonymous accesss - *choose wisely, my friend.*
```PHP
$loggedin_controllers[] = 'Test';
// or, to allow anonymous access
$anonymous_controllers[] = 'Test';
 ```

##### Accessing your endpoint
Your endpoint controller's class name's first letter should be uppercase.  The endpoint will be that class name, but with the first letter's case lowered drastically, and underscores converted to dashes. For example: 
- Forgotten_password becomes /forgotten-password
- Test becomes /test
- Test_table becomes /test-table
- ThisIsAWeirdName becomes /thisIsAWeirdName
 
### <a name="apiexamples"></a>Basic API usage examples
**Login** (GET and POST):
`/login?u=username&p=password`

**Logout** (DELETE):
`/login`

**New user** (POST). Pass form data like email=example@test.com&password=cats:
`/users` An email will be sent to the user's address with their password. The email templates are found in the [email-templates/] directory.

**Change user** (PUT). Pass a JSON string in the payload {"email":"example@test.com", "password":"cats"}:
`/users`
You can also specify your user ID if you want to. You can only update the user you're logged in as, though:
`/users/5` You can even choose a specific field to update if you want to:
`/users/password`
or
`/users/5/pasword`

**View a user** (GET):
`/users`
 Or you can specify your user ID. You can only view the user you're logged in as:
`/users/5`

To trigger a **forgotten password** email (GET, POST):
`/forgotten_password?u=example@test.com`. The user will be sent a code to use in the reset stage.  The email templates are found in the [email-templates/] directory.

To **reset a password** (GET, POST):
`/reset-password?c=[the key in the email]&p=[new password]`

### Config file
[Config.inc.php](inc/config.inc.php) contains a bunch of data you can change to better reflect your API.
```PHP
$config['site'] = array();
$config['site']['name'] = 'API Framework';
$config['site']['location'] = 'http://dev.alteredeffect.com/framework/api/';

// The name and address the emails will come from, and the template location
$config['email'] = array();
$config['email']['name'] = 'Web bot';
$config['email']['address'] = 'bot@dev.alteredeffect.com';
$config['email']['templates'] = 'email-templates/';

// Database connection
$config['database'] = array();
$config['database']['server'] = 'localhost';
$config['database']['user'] = 'username';
$config['database']['password'] = 'password';
$config['database']['database'] = 'api_framework';

// Table names, in case you want to change them
$config['database']['tables'] = array();
$config['database']['tables']['users'] = 'users';
$config['database']['tables']['reset_links'] = 'reset_links';
$config['database']['tables']['logger'] = 'logger';

// User manager settings
$config['users']=array();
$config['users']['reset_timeout'] = 4; // How long a reset link is valid, in hours
	
// Enter all IP addresses where you want $_DEV to be true
$_DEV = (in_array( $_SERVER['REMOTE_ADDR'], array( '65.171.45.102' )));
```

### <a name="generic"></a>Generic table editing
Sometimes you just want to be able to interact with a table via API.  Who am I to judge?

[Generic.controller.php](Generic.controller.php) (the `/generic` endpoint) allows for this, assuming the table in question *has a single Primary key*.  It contains a white-list of tables it can update:
```PHP
private $tables = array('test'); // A list of tables generic can update
```
**GET** `/generic/tablename/` retrieves all the table's data, while `/generic/tablename/primarykeyvalue` returns a single row.

**POST** `/generic/tablename/` adds a row to the table.

**PUT** `/generic/tablename/primarykeyvalue` updates the row with that key value. `generic/tablename/primarykeyvalue/field` updates just that field.

**DELETE** `/generic/tablename/primarykeyvalue` removes the row with that key value.

##### Extending Generic
Implementing a controller for a particular table can allow for a nicer endpoint. For example, [Test_table.controller.php](Test_table.controller.php) turns `/test-table` into the equivalent of `/generic/test`:
```PHP
class Test_table extends Generic {
	public function __construct() {
		global $request;
		$chunks = array_unshift($request, 'test'); // Add the table name back to the front to make this a nice end point
	}
}
```


### Using the libraries
##### <a name="usermanager"></a>UserManager
User passwords are encrypted, but the [UserManager class](blob/lib/UserManager.class.php) takes care of that for you.  And if it doesn't find a users table, it creates one for you at run-time.
```PHP
// ex: $um = new UserManager( $database );
// Where $db is a PDO database connection.
public function __construct( $db );

// Returns the user with the passed id.
public function get( $id );

// Checks if a login is valid. Returns the user ID if it is, otherwise returns false.
public function verifyLogin( $username, $password

// Creates a user.
// If $password isn't passed, a random password will be chosen.
public function createUser( $username, $password=null)

// Updates a user.  
// Where $data is an array like array('email'=>'test@example.com', 'password'=>'mypassword').
// You can leave out fields you don't want updated. 
public function updateUser( $id, $data )
```
Sometimes users forget their passwords, those lovable idiots!
```PHP
// Begins the password reset sequence. An email is sent to the user with a reset code.
public function forgotPassword(* $email )

// Completes the password reset sequence. The user's password is changed.
public function resetPassword( $key, $password 
```
Plus there's a couple of  helper functions.
```PHP
// Creates a pseudo-random string of length $length.
public function generatePass( $length=9 )
```

**You'll probably want to add more fields to your user table eventually**
- modify createUser to if you want to specify default values with code
- modify updateUser's `$fields= array(...)` list to include the new column names 




##### <a name="emails"></a>Email
[Emails.class.php](lib/Emails.class.php) lets you send out emails using templates. By default they are found in the */email-templates* directory, but this can be changed through the [config](#config)'s `$config['email']['templates']`.

```PHP
// Ex: Emails::send('welcome', 'test@example.com', 'Welcome to...the future!', $data);
public static function send( $type, $to, $subject, $fill=array() )
```
The `$type` field tells it which template to use. `'welcome'` would resolve to _email-templates/welcome.html_.  That is, if it exists. If it doesn't, no email will be sent.

Data passed in via the `$fill` array can be used in the emails. It should be an associative array of strings, or other scalar values. It will replace `$key` in the template (where 'key' is its key).
For example, with the following data:
`array( 'name'=>'Jon', 'animal'=>'cats' );`
You could access those values in an email template like this: `$name! I hear you like $animal?'`

##### <a name="logger"></a>Logger
Sometimes it's nice to log run-time messages to the database. You know, for _debugging_.
The [Logger class](lib/Logger.class.php) makes this easy!
```PHP
// Do this somewhere in your code before you call Logger::log, but after you connect to the database
Logger::$database = $database;
```
Don't worry, if the log table (`$config['database']['tables']['logger']`) doesn't exist Logger will make it for you.

Then, anywhere you want to log a message, you can!  It's as easy as `Logger::log( 'something happened!' );`

##### <a name="sessions">Session
Maybe you want your sessions to last for three days, I don't know!	Sometimes you just want more control.
[Session](lib/Session.class.php) stores your session data in the database. It's easy to switch between classic sessions and the session library. It's as simple as:
```PHP
session_start();
// Or, to use the Sessions library, do this instead
Session::$database = $database;
Session::start();
```
That's all there is to it! You can just play with the `$_SESSIONS` superglobal as usual, and everything will be taken care of for you.

If you need to change some of Session's internal settings:
```PHP	
public static $ttl = 3600; // 1 hour
private static $cookiename = 'S_SID';
private static $tablename = 'sessions';
```