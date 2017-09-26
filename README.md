
# Laravel API Docs Generator

This Laravel package provides an API Documentation generator based upon your Routes and Controller Method DocBlock comments.

## Installation

#### Laravel 5.0

Begin by installing this package through Composer. Edit your project's `composer.json` file to require `f2m2/apidocs`.

    "require-dev": {
        "insanelab/apidocs": "dev-master"
    }

Next, update Composer from the Terminal:

    composer update --dev

Once the packaage has installed, the final step is to add the service provider. Open `config/app.php`, and add a new item to the providers array.

    'Insanelab\Apidocs\ApidocsServiceProvider',

Run the `artisan` command from the Terminal to see the new `apidocs` command.

    php artisan apidocs:generate

Create a copy of the API Docs Config by running this `artisan` command:

    php artisan vendor:publish


#### Laravel 4.2

Our fork does not support Laravel below 5.0


Notes
-------

##### Route Prefix

Create a prefix for your routes with an API Version.  i.e. 'api/v1

    Route::group(['prefix' => 'api/v1'], function(){
         // ...
    });

##### DocBlock Example
Below is a docBlock example.

    /**
    * Display the specified resource.
    * GET /user/{id}
    *
    * @param  integer  $id  The id of a User
    * @return Response
    */
    public function show($id)
    {
        // Display User
    }

License
-------

See [LICENSE](LICENSE.md) file.

