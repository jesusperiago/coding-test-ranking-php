# Reto: Servicio para gestión de calidad de los anuncios

Este repositorio contiene un API parcialmente desarrollada para desarrollar un servicio que se encargue de medir la calidad de los anuncios. Tu objetivo será implementar las historias de usuario que se describen más adelante.

Los supuestos están basados en un hipotético *equipo de gestión de calidad de los anuncios*, que demanda una serie de verificaciones automáticas para clasificar los anuncios en base a una serie de características concretas.

## Historias de usuario

* Yo, como encargado del equipo de gestión de calidad de los anuncios quiero asignar una puntuación a un anuncio para que los usuarios de idealista puedan ordenar anuncios de más completos a menos completos. La puntuación del anuncio es un valor entre 0 y 100 que se calcula teniendo encuenta las siguientes reglas:
  * Si el anuncio no tiene ninguna foto se restan 10 puntos. Cada foto que tenga el anuncio proporciona 20 puntos si es una foto de alta resolución (HD) o 10 si no lo es.
  * Que el anuncio tenga un texto descriptivo suma 5 puntos.
  * El tamaño de la descripción también proporciona puntos cuando el anuncio es sobre un piso o sobre un chalet. En el caso de los pisos, la descripción aporta 10 puntos si tiene entre 20 y 49 palabras o 30 puntos si tiene 50 o mas palabras. En el caso de los chalets, si tiene mas de 50 palabras, añade 20 puntos.
  * Que las siguientes palabras aparezcan en la descripción añaden 5 puntos cada una: Luminoso, Nuevo, Céntrico, Reformado, Ático.
  * Que el anuncio esté completo también aporta puntos. Para considerar un anuncio completo este tiene que tener descripción, al menos una foto y los datos particulares de cada tipología, esto es, en el caso de los pisos tiene que tener también tamaño de vivienda, en el de los chalets, tamaño de vivienda y de jardín. Además, excepcionalmente, en los garajes no es necesario que el anuncio tenga descripción. Si el anuncio tiene todos los datos anteriores, proporciona otros 40 puntos.

* Yo como encargado de calidad quiero que los usuarios no vean anuncios irrelevantes para que el usuario siempre vea contenido de calidad en idealista. Un anuncio se considera irrelevante si tiene una puntación inferior a 40 puntos.

* Yo como encargado de calidad quiero poder ver los anuncios irrelevantes y desde que fecha lo son para medir la calidad media del contenido del portal.

* Yo como usuario de idealista quiero poder ver los anuncios ordenados de mejor a peor para encontrar fácilmente mi vivienda.

## Consideraciones importantes

En este proyecto te proporcionamos un pequeño *esqueleto* escrito en PHP 8 usando Symfony Flex.

En dicho *esqueleto* hemos dejado varios Controllers y un Repository en el sistema de ficheros como orientación. Puedes crear las clases y métodos que consideres necesarios.

Podrás ejecutar el proyecto utilizando la configuración de Docker que dejamos en el mismo *esqueleto* e instalando a través de composer los paquetes necesarios.

**La persistencia de datos no forma parte del objetivo del reto**. Si no vas a usar el esqueleto que te proporcionamos, te sugerimos que la simplifiques tanto como puedas (con una base de datos embebida, "persistiendo" los objetos en memoria, usando un fichero...). **El diseño de una interfaz gráfica tampoco** forma parte del alcance del reto, por tanto no es necesario que la implementes.

**Nota:** No estás obligado a usar el proyecto proporcionado. Si lo prefieres, puedes usar cualquier framework y/o librería. Incluso puedes prescindir de estos últimos si consideras que no son necesarios. A lo que más importancia damos es a tener un código limpio, de calidad y a las explicaciones que nos des sobre tus decisiones de diseño.  

### Requisitos mínimos

A continuación se enumeran los requisitos mínimos para ejecutar el proyecto:

* PHP 8
* Symfony Local Web Server o Nginx.

## Criterios de aceptación

* El código debe ser ejecutable y no mostrar ninguna excepción.

* Debes proporcionar 3 endpoints: Uno para calcular la puntuación de todos los anuncios, otro para listar los anuncios para un usuario de idealista y otro para listar los anuncios para el responsable del departamento de gestión de calidad.


# Solución

## RUTAS
Modificado fichero [routes.yaml](/config/routes.yaml) creando los 3 endpoints
* Calcular la puntuación de todos los anuncios: [/calculate](http://localhost:8080/calculate) 
* Listar los anuncios para un usuario de idealista: [/listing](http://localhost:8080/listing)
* Listar los anuncios para el responsable del departamento de gestión de calidad.[/quality](http://localhost:8080/quality)


## Ficheros modificados y características
* En el [docker file](/docker/php/Dockerfile) se ha modificado la versión de php denido a errores con php 8.0. Y ajustada la ruta para que funcione con symfony 5
* En el [Domain Ad](/src/Domain/Ad.php) se han creado getter y setter
* En el [Domain Picture](/src/Domain/Picture.php) se ha creado getter y setter y añadido método para comprobar si una imagen es HD
* En fichero [Persistence InFileSystemPersistence](/src/Infrastructure/Persistence/InFileSystemPersistence.php) se han añadido metodo par recuperar el array de Ads y un método para recuperar una imagen por su ID
* En [CalculateScoreController](/src/Infrastructure/Api/CalculateScoreController.php) se ha añadido toda la lógica para calcular el Score.
* En [PublicListingController](/src/Infrastructure/Api/PublicListingController.php) se ha añadido toda la lógica para mostrar la lista de anuncios que tienen un puntuación superior a 40.
* En [QualityListingController](/src/Infrastructure/Api/QualityListingController.php) se ha añadido toa la lógica para mostrar la lista de anuncios que son irrelevantes.
* Se almacena en caché los datos de los anuncios. No está realizada la gestión de caché. Se genera siempre al entrar en la ruta de cálculo. 
* Modificado [Services](/config/services.yaml) donde se crearon varios servicios y dependencias

# Instalación y ejecución
## Docker
* Ejecutar en la raiz
```bash
docker compose -f "docker\docker-compose.yml" up -d --build 
```
* En la carpeta raiz del proyecto ejecutar 
```bash
composer install
```
* Desde el navegador accedemos a la url [http://localhost:8080](http://localhost:8080)

## Symfony Web Server
* Instalar Symfony y Compose
* En la carpeta raiz del proyecto ejecutar 
```bash
composer install
```
* Una vez instalado ejecutar
```bash
symfony serve
```
