######
Request Route Response - Workflow
Edit Date: 5/18/25
Author: Chris McIntosh
Licencse: GPU-3.0
Email: chris@httpstack.tech
#####


### 1. **Route Definition**
- **File:** web.php
- **Action:** Import the controller with use statement.
- **Action:** Add a new route using the router instance.
- **Example:**
  ```php
  use Framewrork\Controllers\NewFeatureController;
  $router->get('/new-feature', [NewFeatureController::class, 'index']);
  ```

---

### 2. **Controller**
- **File:** `app/Controllers/NewFeatureController.php`
- **Receive/Return:** The 'index' method specified in the definition 
                            [NewFeatureController::class, 'index']
                        will **Receive** the captured Request from the dispatcher.
                        The class __construct() will need to create a instance
                        Response and the method returning, must **Return** a Response

- **Class Skeleton:**
  ```php
  namespace App\Controllers;

  use Framework\Http\Request;
  use Framework\Http\Response;

  class NewFeatureController
  {
        public function __construct()
        {
            // Constructor logic if needed
            global $app;
            $this->app = $app;
            $this->container = $app->getContainer();
            $this->template = $this->container->make('template');
            $this->response = new Response();
        }
        public function index(Request $request): Response
        {
            //Optional Templating
            //If not called, will not have any content in response
            $this->applyTemplate('new-feature/index');

            //Somethin must be set to the Response body
            if(!$this->response->getBody()){
                $this->response->setBody("Home Page. No Template");
            }
            return $this->response;
        }
  }
  ```

---

### 3. **Template (Optional)**
- **File:** `templates/new-feature/index.html`
- **Purpose:** If you want to render a view, create the corresponding template file.

---

### 4. **Middleware (Optional)**
- **File:** Middleware
- **Purpose:** If you need route-specific middleware, define it and attach it to the route.
                Middleware must have a process method if set with only the ClassName
                the same **Receive/Return** as in #2
                **Only Response Headers are set in the MiddleWare**, the body is still empty
                upon reaching the BaseController
```php
    $router->middleware(TemplateMiddleware::class);
```
---

### 5. **Service Registration (If Needed)**
- **File:** AppServiceProvider.php
- **Purpose:** Register any new services/models your controller might need in the container.

---

### 6. **Data Model (Optional)**
- **File:** Models
- **Purpose:** If your feature needs a model, create it extending `BaseModel`.

---

### 7. **Application Boot/Run**
- **File:** index.php
- **Flow:**
  1. App is initialized, services loaded into the container.
  2. Router is initialized.
  3. web.php is included, defining routes.
  4. Global middleware is registered (e.g., template/model).
  5. `app->boot()` and `app->run()` are called.
  6. The request is captured and dispatched to the router.
  7. The router matches the route, calls the controller method, and expects a `Response`.
  8. The response is sent to the client.

---

### **Summary of Required Files/Classes for a New Route**
- web.php (route definition)
- `app/Controllers/NewFeatureController.php` (controller)
- `templates/new-feature/index.html` (optional view)
- `app/Models/YourModel.php` (optional model)
- `app/Middleware/YourMiddleware.php` (optional middleware)
- AppServiceProvider.php (optional service registration)

---

If you want to add something else to the flow (e.g., custom middleware, event hooks, or service injection), let me know what you have in mind and I can show you how to integrate it!