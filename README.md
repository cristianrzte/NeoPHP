NeoPHP
======

<h3>1. Atributos</h3>
  - Patrón de diseño MVC (Modelo Vista Controlador)
  - Clases ordenadas de manera jerarquica y estructuradas mediante nomenclatura especifica
  - Unico punto de ingreso (index.php)
  - Soporte para multiples lenguages
  - Soporte para logueo de información, errores, advertencias, etc
  - No se utilizan nunca variables $_GET, $_POST o $_REQUEST. Estas se mapean en argumentos en las acciones de los controladores lo que hace que quede todo mucho más prolijo.
  - No se utilizan variables $_SESSION, La sesión se usa a través de una clase especial que maneja dicha variable,
  - Para base de datos no se pone NADA de SQL, las tablas están modeladas como objetos y a través de métodos se puede hacer búsquedas, inserciones, eliminaciónes, etc. Todas las consultas se hacen de manera homogenea y transparentes al que programe por afuera del framework y además utiliza PDO con lo cual no importa la base de datos que este corriendo atrás.

<h3>2. Estructura</h3>

<pre>
{proyectname}/
  index.php             Punto de ingreso a la aplicación
  app/                  Carpeta que contiene todos los archivos de aplicación
    connections/        Contiene todas las conexiones a base de datos que se utilicen
    controllers/        Contiene los controladores (capa de lógica de negocios)
    models/             Contiene todos los tipos las entidades
    resources/          Contiene archivos de idioma para la aplicación
    utils/              Contiene clases de utilidades generales
    views/              Contiene todas las vistas de la aplicación (capa de presentación)
    widgets/            Contiene componentes creados por el usuario para ser usados en las vistas
    App.php             Clase principal de la aplicación. A través de este objeto se tiene acesso a cualquier lugar
    Connection.php      Clase para manejo de una conexión a Base de Datos
    Controller.php      Clase del cual extender para crear un Controlador
    DataObject.php      Clase para crear objetos que tengan interacción con la base de datos
    Logger.php          Clase que se encarga de loguear a archivos errores e información
    Model.php           Clase del cual extender para crear un Modelo
    Preferences.php     Clase para almacenar cualquier configuración o preferencia de la aplicación
    Session.php         Clase para el manejo de Sesión
    Translator.php      Clase para manejar traducción en distintos idiomas
    View.php            Clase del cual extender para crear Vistas
  assets/               Contiene librerías de terceros utilizadas por la aplicación
  css/                  Contiene archivos de estlios
  images/               Contiene imagenes utilizadas en la aplicación (no dependientes del estilo o tema)
  js/                   Contiene archivos javascript utilizadas en la aplicación
  logs/                 Contiene todos los archivos de logs generados
</pre>

<h3>3. Funcionamiento</h3>

<h4>2.1. Controladores</h4>

Se utiliza solo 1 url y una acción asociada, es decir supongamos que el proyecto se llama "azureus", entonces la url para acceder a las paginas (o servicios web) va a ser del tipo "http://localhost/azureus/?action=???". 
La acción es un string separado por barras que indica que controlador va a atender la petición y que función dentro de dicho controlador es el que se va a ejecutar.

Por ejemplo, si la acción es *site/user/addUser* entonces el framework va a buscar en la carpeta *app/controllers/site/* el controlador de nombre *UserController* y dentro de dicho controlador va a llamar a la función *addUserAction*.
Si se especifica solo *site/user/* entonces se va a llamar a la funcion *defaultAction* dentro del controlador UserController.
Por último, si no se especifica ninguna acción entonces se va a ejecutar la acción *defaultAction* dentro del controlador *MainController* en *app/controllers/*

Si se quisiera hacer el famoso "Hola Mundo" quedaría de la siguiente manera:

`````php
<?php
class MainController extends Controller
{
    public function defaultAction ()
    {
        echo "hola mundo";
    }
}
?>
`````

Si a una acción le llegan variables GET o POST, estas llegan mapeadas de forma automática como argumentos de la acción, de la siguiente manera

`````php
<?php
class SiteController extends Controller
{
    public function loginAction ($username, $password)
    {
        //En este caso las variables $_POST['username'] y $_POST['password']
        //fueron mapeadas *automaticamente* en los argumentos de la funcion
    }
}
?>
`````

La idea con los controladores es crear controladores que agrupen funcionalidad sobre 1 mismo aspecto, es decir podriamos crear un controlador que maneje toda la lógica de usuarios, por ejemplo una clase UserController que tenga el siguiente formato:

`````php
<?php
class UsersController extends Controller
{
    public function addUserAction ($firstname, $lastname, $username, $password)
    {
        //Agregar un usuario a la base de datos
    }
    
    public function updateUserAction ($userid, $firstname, $lastname, $username, $password)
    {
        //Actualizar un usuario en la base de datos
    }
    
    public function deleteUserAction ($userid)
    {
        //Eliminar un usuario de la base de datos
    }
    
    public function showUserFormAction ($userid)
    {
        //Renderizar una vista que muestre un formulario de inserción/actualización de usuarios
    }
    
    public function showUserDataAction ($userid)
    {
        //Renderizar una vista que muestre la información del usuario
    }
}
?>
`````

<h4>2.2. Vistas</h4>

Crear vistas es muy facil, todas las vistas heredan de una clase "View" que contiene un método "render", y hay una clase incluida en el framework, que es la clase HTMLView que te permite crear vistas de tipo HTML. Si se quiere crear un vista con el clasico "hola mundo" seria de la siguiente manera:

Paso 1: Crear un archivo PHP llamado "HelloWorldView.php" dentro de la carpeta "view"

`````php
<?php
require_once ("app/views/HTMLView.php");
class HelloWorldView extends HTMLView
{
    protected function build()
    {
        parent::build();
        $this->buildHead();
        $this->buildBody();
    }
   
    protected function buildHead ()
    {
        $this->headTag->add(new Tag("title", array(), "Hello World Example"));
        $this->headTag->add(new Tag("meta", array("http-equiv"=>"content-type", "content"=>"text/html; charset=UTF-8")));
        $this->headTag->add(new Tag("meta", array("name"=>"language", "content"=>"es")));
    }
   
    protected function buildBody ()
    {
        $this->bodyTag->add($this->createHelloWorldPanel());
    }
    
    protected function createHelloWorldPanel ()
    {
        return new Tag("div", array("class"=>"helloWorldPanel"), new Tag("span", array(), "HelloWorld"));
    }
}
?>
`````

Paso 2: Crear una acción que renderize la vista

`````php
<?php
class MainController extends Controller
{
    public function defaultAction ()
    {
        App::getInstance()->getView("helloWorld")->render ();
    }
}
?>
`````

El resultado en HTML de ejecutar el método render a esta vista será el siguiente

`````html
<!DOCTYPE html>
<html>
    <head>
        <title>Hello World Example</title>
        <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
        <meta name="language" content="es" />
    </head>
    <body>
        <div class="helloWorldPanel">
            <span>HelloWorld</span>
        </div>
    </body>
</html>
`````

Eventualmente se podría configurar ciertas cosas a la vista antes de renderizarla, por ejemplo se podría hacer lo siguiente:

`````php
$helloWorldView = App::getInstance()->getView("helloWorld");
$helloWorldView->setHelloWorldText ("Hola Mundito");
$helloWorldView->render();
`````

Notece que dentro de las vistas no debería haber ninguna logica de negocios (solo el renderizado de datos). La lógica, como por ejemplo inserciónes/actualización en base de datos deberían ser hechas en los controladores.

<h4>2.3. Traducciones</h4>

Las traducciones se hacen utilizando la clase Translator. Utiliza una nomenclatura especial para poder cargar correctamente los archivos de idiomas. Los archivos de idioma se crean en la carpeta resources. Ahi se puede crear una estructura jerarquiqua de carpetas finalizando con archivos .ini en donde estaran finalmente los textos en los distintos idiomas.

El archivo .ini de idioma debe tener la siguiente estructura

`````ini
[es]
firstname = nombre
lastname = apellido

[en]
firstname = FirstName
lastname = LastName
`````

Luego desde PHP se debe especificar el idioma con el que trabajará, por defecto se utilizara el idioma predeterminado del servidor web. Para establecer el idioma hay que ejecutar la siguiente sentencia

`````php
App::getInstance()->getTranslator()->setLanguage("pt");
`````

Finalmente para la obtención de los textos traducidos hay que llamar a la funcion getText. A continuación se muestran algunos ejemplos:


`````php
App::getInstance()->getTranslator()->getText("car");  //Buscará "car" en el archivo *resources/default.ini*
App::getInstance()->getTranslator()->getText("general.firstname");  //Buscará "firstname" en el archivo *resources/general.ini*
App::getInstance()->getTranslator()->getText("views.aboutus.welcome");  //Buscará "welcome" en el archivo *resources/views/aboutus.ini*
`````
<h4>2.4. Logueo</h4>

Para loguear a archivos los errores, las advertencias o simplemente información de la aplicación se utiliza la clase Logger.
Los archivos de logs se guardan en la carpeta *logs* en archivos .txt con el timestamp de la fecha en la que fue generada la entrada de log.
Existen diferentes niveles de logueo que se pueden utilizar para el logueo, estos son: FINE, INFO, NOTICE, WARNING y ERROR.
Por defecto se generan automaticamente entradas de logueo de tipo ERROR ante una excepción en la aplicación.

Algunos ejemplos de entradas de logueo

`````php
App::getInstance()->getLogger()->fine ("Se esta creando la base de datos ...");
App::getInstance()->getLogger()->info ("La base de datos cuenta con 57 tablas");
App::getInstance()->getLogger()->warning ("Algunos indices de tablas no se pudieron crear !!");
`````

Los archivos de logueo generados tienen el siguiente formato

<pre>
[23.02.2013 11:07:20] FINE: Se esta creando la base de datos ...
[23.02.2013 11:10:13] INFO: La base de datos cuenta con 57 tablas
[23.02.2013 11:10:28] WARNING: Algunos indices de tablas no se pudieron crear !!
</pre>

En caso de errores, se loguea el stacktrace

<pre>
[23.02.2013 11:07:20] ERROR: exception "ErrorException" with message "No such file or directory" in /var/www/Blueshark/app/views/institutionalSite/ContactUsView.php:30
Stack trace:
#0 /var/www/Blueshark/app/views/institutionalSite/ContactUsView.php(30): App::errorHandler(2, 'include_once(ap...', '/var/www/Bluesh...', 30, Array)
#1 /var/www/Blueshark/app/views/institutionalSite/ContactUsView.php(30): ContactUsView::createInfoPanel()
#2 /var/www/Blueshark/app/views/institutionalSite/ContactUsView.php(24): ContactUsView->createInfoPanel()
#3 /var/www/Blueshark/app/views/institutionalSite/InstitutionalSiteView.php(40): ContactUsView->createBodyContent()
#4 /var/www/Blueshark/app/views/institutionalSite/InstitutionalSiteView.php(33): InstitutionalSiteView->createPage()
#5 /var/www/Blueshark/app/views/institutionalSite/InstitutionalSiteView.php(15): InstitutionalSiteView->buildBody()
#6 /var/www/Blueshark/app/views/HTMLView.php(29): InstitutionalSiteView->build()
#7 /var/www/Blueshark/app/controllers/InstitutionalSiteController.php(34): HTMLView->render()
#8 [internal function]: InstitutionalSiteController->showContactUsAction()
#9 /var/www/Blueshark/app/Controller.php(27): call_user_func_array(Array, Array)
#10 /var/www/Blueshark/app/App.php(69): Controller->executeAction('showContactUs', Array)
#11 /var/www/Blueshark/index.php(3): App->executeAction('institutionalSi...')
#12 {main}
</pre>

IMPORTANTE: Para el correcto funcionamiento de la clase de logue es necesario que la carpeta en donde se guardan los logs tenga permisos de escritura.

<h4>2.5. Sesión</h4>

Para manejo de sesión se tiene que usar la clase Session, se usa de la siguiente manera.
 
Para iniciar sesión
`````php
App::getInstance()->getSession()->startSession();
App::getInstance()->getSession()->userName = "pepech";
App::getInstance()->getSession()->firstName = "pepe";
App::getInstance()->getSession()->lastName = "paredes";
`````

Para acceder a datos de sesion
`````php
echo App::getInstance()->getSession()->userName;
`````

Para cerrar sesión
`````php
App::getInstance()->getSession()->destroy();
`````

<h4>2.6. Base de datos</h4>

Para base de datos se usan las clases "Connection" y "DataObject"
Para crear una nueva conexión a base de datos se tiene que crear una clase de tipo "xxxConnection" que extienda de Connection con ciertos parametros. Utiliza PDO por consiguiente no importa con que base de datos con la que este conectado atras. 
Se pueden crear más de 1 conexión a base de datos, por ejemplo se podría tener una conexión a una base de datos en producción y o otra a una de pruebas, o sea, se podría tener 2 clases que hereden de Connection, Una llamada ProductionConnection y otra llamada DevelopmentConnection (creadass dentro de la carpeta connections)

Por ejemplo, el archivo de conexión a la de producción, a una base de datos mysql, sería de la siguiente manera:

`````php
<?php
class ProductionConnection extends Connection
{
    public function getDsn ()
    {
        return "mysql:host=localhost;dbname=telemetrix";
    }
    
    public function getUsername ()
    {
        return "root";
    }
    
    public function getPassword ()
    {
        return "root";
    }
    
    public function getDriverOptions ()
    {
        return array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
    }
}
?>
`````

Para acceder a dichas conecciones se hace de la siguiente manera

`````php
App::getInstance()->getConnection("production");
App::getInstance()->getConnection("development");
`````

2.5.1. Consultas SQL

Si quisieramos hacer "SELECT * FROM User" e iterar por los resultados deberíamos hacer lo siguientes

`````php
$connection = App::getInstance()->getConnection("production");
$doUser = $connection->getDataObject("User");
$doUser->find();
while ($doUser->fetch())
{
    echo $doUser->username;
    echo "<br>";
    echo $doUser->password;
}
`````

Tambien podrías obtener el resultSet en forma de array con el método fetchAll
$resultSet = $doUser->fetchAll();

Si quisieramos hacer "SELECT username FROM user WHERE username="pepech" AND password="123"

`````php
$connection = App::getInstance()->getConnection("production");
$doUser = $connection->getDataObject("User");
$doUser->addSelectField ("username");
$doUser->addWhereCondition("username='pepech'");
$doUser->addWhereCondition("password='123'");
$doUser->find(true); //El true indica que hace un fetch automatico
echo $doUser->username;
`````

Si quisieramos hacer por ejemplo "SELECT user.username AS user_username, user.password AS user_password, person.firstname AS person_firstname, person.lastname AS person_lastname FROM user INNER JOIN person ON user.personid = person.personid"

`````php
$connection = App::getInstance()->getConnection("production");
$doUser = $connection->getDataObject("User");
$doPerson = $connection->getDataObject("Person");
$doUser->addSelectFields (array("username", "password"), "user_%s", "user");
$doUser->addSelectFields (array("firstname", "lastname"), "person_%s", "person");
$doUser->addJoin($doPerson, DataObject::JOINTYPE_INNER, "personid");
$doUser->find()
{
    echo $doUser->user_username;
    echo "<br>";
    echo $doUser->person_firstname;
}
`````

Si quisieramos hacer por ejemplo un insert "INSERT INTO User (username, password) VALUES ("pepe", "123")" sería de la siguiente manera

`````php
$connection = App::getInstance()->getConnection("production");
$doUser = $connection->getDataObject("User");
$doUser->username = "pepe";
$doUser->password = "123";
$doUser->insert();
`````

Con PDO::lastInsertId() podes obtener cual fue el indice que se utilizo para el último insert

Si quisieramos "UPDATE User SET password = "456" WHERE username = "pepe"" sería

`````php
$connection = App::getInstance()->getConnection("production");
$doUser = $connection->getDataObject("User");
$doUser->password = "456";
$doUser->addWhereStatement("username='pepe'");
$doUser->update();
`````

<h3>4. Instalación y puesta en marcha</h3>
Solo se tiene que copiar el contenido de la carpeta "sources" al raiz de un proyecto nuevo y listo, de ahi en más ya se puede empezar a crear controladores propios y vistas dentro del mismo.

Es posible que en entornos *Windows* haya que configurar en el archivo de configuración de apache (httpd.conf) el DocumentIndex para que apunte a index.php en lugar de index.html

Es recomendado utilizar ciertas configuraciones en el php.ini (no obligatorias), estas son:
  - session.auto_start = 1
  - session.use_cookies = 1
  - session.use_trans_sid = 0;

