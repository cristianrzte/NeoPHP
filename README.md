![](https://img.shields.io/travis/luismanuelamengual/NeoPHP.svg) 
![](https://img.shields.io/github/license/luismanuelamengual/NeoPHP.svg)
![](https://img.shields.io/github/forks/luismanuelamengual/NeoPHP.svg?style=social&label=Fork)
![](https://img.shields.io/github/stars/luismanuelamengual/NeoPHP.svg?style=social&label=Star)
![](https://img.shields.io/github/watchers/luismanuelamengual/NeoPHP.svg?style=social&label=Watch)
![](https://img.shields.io/github/followers/luismanuelamengual.svg?style=social&label=Follow)

# NeoPHP
Great PHP framework for web developers, not for web 'artisans' (wtf ??!!)

Getting started
---------------
To install the NeoPHP framework we have to **run the following command (Composer required)**, assuming we want to start a new project named "MyApp":
```
composer create-project neogroup/neophp-startup-project MyApp
```
This command will create an empty NeoPHP project. The structure of the created proyect will be as follows ...

```
MyApp                             Base application path
├─ config                         Directory where all config files are located
│  ├─ app.php                     General application configuration file
│  ├─ database.php                Database configuration file
│  ├─ logging.php                 Logging configuration file
│  ├─ resources.php               Resources configuration file
│  ├─ views.php                   Views configuration file
│  └─ models.php                  Models configuration file
├─ public                         Directory for all public resources (css, js, assets, etc)
│  ├─ bower_components            Directory for all bower components
│  ├─ components                  Directory for components (resources that have css, js, imges, etc)
│  ├─ css                         Directory for style sheet files
│  ├─ img                         Directory for images
│  ├─ js                          Directory for javascript files
│  ├─ .htaccess                   File that handles the site requests and redirect them to index.php
│  ├─ favicon.ico                 Icon the the application
│  ├─ index.php                   Starting main point for the application
│  └─ robots.txt                  Bot detector configuration file
├─ resources                      Directory for all the application resources
│  ├─ messages                    Base directory for translation bundles
│  └─ views                       Base directory for views (templates basically)
├─ src                            Base directory for source files (php classes)
├─ storage                        Base directory for all generated files
│  ├─ framework                   Directory for files that are generated by the framework
│  └─ logs                        Directory for the application logs
├─ vendor                         Base directory for composer packages
├─ .bowerrc                       Bower configuration file
├─ .gitignore                     Git ignrations file
├─ bower.json                     Bower json file for web requirements
├─ composer.json                  Composer json file for PHP requirements
├─ composer.lock                  Composer file the indicates the installed PHP dependencies
├─ LICENSE.md                     License file
└─ README.md                      Readme file
```

Now we have to **add write permissions to the folder "storage"** (this is the place where logs and compiled views are stored). In linux system you can run the following commands
```
cd MyApp
chmod 777 -R storage/
```
The next and final step is to configure the **public** directory. You should **configure your web server's document / web root to be the public directory**. The index.php in this directory serves as the front controller for all HTTP requests entering your application.

Properties
---------------

Controllers
---------------
Controllers can be **any class in the "src" folder**. These controllers are places where we are going to put the business logic. This is an example of a simple controller that writes "hello world" in the browser ...

```PHP
<?php

namespace MyApp;

class HelloWorldController {
    
    public function sayHello () {
        echo "Hello world !!";
    }
}
```
We can **execute controller methods like "sayHello"** in the following way
```PHP
get_app()->execute("MyApp\HelloWorldController@sayHello");
```
If you dont specify any method for the controller then the **default method "index" will be executed**. Example:
```PHP
get_app()->execute("MyApp\HelloWorldController");
```
Its also possible to **pass arguments to the controller methods**. If we modify the controller a bit like this ...
```PHP
<?php

namespace MyApp;

class HelloWorldController {
    
    public function sayHello ($name) {
        echo "Hi $name, hello world !!";
    }
}
```
Then its possible to pass the name parameters as follows ...
```PHP
get_app()->execute("MyApp\HelloWorldController@sayHello", ["name"=>"Luis"]);
```
The **Boot controller actions** (Actions that are executed on every php request) can be configured in the configuration file **"app.php" in the config folder** with the **bootActions** property. Suppose we want to execute our sayHello method on every php request then the app.php configuration file could look like this ...
```PHP
<?php

return [

    "debug"=>false,

    "bootActions"=> [
        "MyApp\HelloWorldController@sayHello"
    ]
];
```

Routing
---------------
Routes are a way execute a controller method or a basic closure which matches a certain request path and method.

Basic closure routes are routes that executes a simple callback function. This is an example ...
```PHP
Routes::get("/helloworld", function () {
    echo "Hello World !!";
});
```
In this example, when we enter in the browser the url "/helloworld" then "Hello World !!" will be printed in the screen.

Other type of routes are the ones that executes controller actions. Example:
```PHP
Routes::get("/helloworld", "MyApp\HelloWorldController@sayHello");
```
If we add request parameters to the http request then the controller method can receive them as parameters. For example if run the uri **/helloworld?name=Luis** then the parameter "name" will be passed to the controller action execution and therefore this **parameter "name" will be accesible in the controller method**

These are the **availiable methods** that may be matched with routes
```PHP
Routes::get($uri, $callback);
Routes::post($uri, $callback);
Routes::put($uri, $callback);
Routes::delete($uri, $callback);
```
Its also possible to match any http method with the **any method**
```PHP
Routes::any($uri, $callback);
```
Wildcards can be used to match any path starting with a desired context. To use wildcards the * is used in the path. These are valid examples ..

```PHP
Routes::get("*", "MyApp\MainController@path");
Routes::post("test/*", "MyApp\Test\TestController");
Routes::put("/resources/users/*", function() { echo "test"; });
```
Routes with **path parameters may be declared using the : prefix** in the path. For example ..

```PHP
Routes::get("users/:userId", "MyApp\Users\UsersController@findUser");
```
Then the "userId" parameter may be accesible as a controller method parameter as follows ..
```PHP
<?php

namespace MyApp\Users;

class UsersController {
    
    public function findUser ($userId) {
        echo "Trying to find the user $userId";
    }
}
```
Registering routes that executes before or after certain routes can be achieved using the **before and after methods** as follows ...
```PHP
Routes::before("test", function() { echo "This function executes before the test route"; });
Routes::get("test", function() { echo "This is the actual route"; });
Routes::after("test", function() { echo "This execute after the test route; });
```
The before routes are specially usefull for session validations or for input transformations. Example: 
```PHP
Routes::before("site/*", function() { 
    if (!get_session()->isStarted()) {
        header("location: portal");
    }
});
```
In this example all requests to the context "site/" will have session validation and redirect to portal if no session is started

The after routes are specially usefull for output transformations. The result of the route is stored in the result parameter. This result may be modified to return another output. Example ...
```PHP
Routes::any("/persons/", function() { 
    return [{ "name"=>"Luis", "lastname"=>"Amengual", "age"=>35 }];
});
Routes::after("persons", function($result) {
    switch (get_request()->get("output")) {
        case "json":
            $result = json_encode($result);
            break;
    }
    return $result;
});
```
In this example if we run the uri "/persons?output=json" then response will be in json format

Its possible also to define error routes for certain contexts with the "error" method. The exeption is passed to the controller as follows ..
```PHP
Routes::error("resources/*", function($exception) { 
    echo "Houston we have a problem !!. Message: " . $exception->getMessage();
});
```

Database
---------------
The database configuration for your application is located at config/database.php. In this file you may define all of your database connections, as well as specify which connection should be used by default.

If no connection is defined then the default connection is used. Raw sql statements can be executed with the methods query and exec of the DB class to the default connection as follows.
```PHP
DB::query($sql, array $bindings = []);
DB::exec($sql, array $bindings = []);
```
Examples
```PHP
$persons = DB::query("SELECT * FROM person");
$personsOver20 = DB::query("SELECT * FROM person WHERE age > ?", 20); 
DB::execute("INSERT INTO person (name, lastname, age) VALUES ('Luis','Amengual',20);
```
Using multiple connections is possible using the connection method as follows
```PHP
$persons = DB::connection("secondary")->query("SELECT ...");
$persons = DB::connection("test")->exec("INSERT INTO ...")
```

Resources
---------------

Models
---------------

Views
---------------

Logging
---------------

Messages & translation
---------------
