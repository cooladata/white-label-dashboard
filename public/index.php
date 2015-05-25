<?php
    require_once "../userfrosting/config-userfrosting.php";
    

    use UserFrosting as UF;
   
    // Front page
    $app->get('/', function () use ($app) {
        // Forward to the user's landing page (if logged in), otherwise take them to the home page
        if ($app->user->isGuest()){
            $controller = new UF\AccountController($app);
            $controller->pageHome();
        } else {
            $app->redirect($app->user->landing_page);        
        }
    })->name('uri_home');

    // User pages
    $app->get('/zerg', function () use ($app) {    
        $controller = new UF\UserController($app);
        $controller->pageZerg();
    });

    $app->get('/dashboard', function () use ($app) {    
        $controller = new UF\UserController($app);
        $controller->pageDashboard();
    });
    
    // Alert stream
    $app->get('/alerts', function () use ($app) {
        $controller = new UF\BaseController($app);
        $controller->alerts();
    });
    
    // JS Config
    $app->get('/js/config.js', function () use ($app) {
        $controller = new UF\BaseController($app);
        $controller->configJS();
    });
    
    // Theme CSS
    $app->get('/css/theme.css', function () use ($app) {
        $controller = new UF\BaseController($app);
        $controller->themeCSS();
    });
        
    
    // Account management pages
    $app->get('/account/:action', function ($action) use ($app) {    
        $controller = new UF\AccountController($app);
    
        switch ($action) {
            case "login":               return $controller->pageLogin();
            case "logout":              return $controller->logout(); 
            case "register":            return $controller->pageRegister();
            case "resend-activation":   return $controller->pageResendActivation();
            case "forgot-password":     return $controller->pageForgotPassword($app->request()->get('token'));
            case "captcha":             return $controller->captcha(); 
            default:                    return $controller->page404();   
        }
    });

    $app->post('/account/:action', function ($action) use ($app) {    
        $controller = new UF\AccountController($app);
    
        switch ($action) {
            case "login":               return $controller->login();     
            case "register":            return $controller->register();
            case "resend-activation":   return $controller->resendActivation();
            case "forgot-password":     return $controller->forgotPassword($app->request()->get('token'));    
            default:                    return $controller->page404();   
        }
    });    
    
    // Not found page (404)
    $app->notFound(function () use ($app) {
        $controller = new UF\BaseController($app);
        $controller->page404();
    });

    // Admin tools
    $app->get('/config/settings', function () use ($app) {
        // Access-controlled page
        if (!$app->user->checkAccess('uri_site_settings')){
            $app->notFound();
        }
        
        // Hook for core and plugins to register their settings
        $app->applyHook("settings.register");
        
        $app->render('site-settings.html', [
            'page' => [
                'author' =>         $app->site->author,
                'title' =>          "Site Settings",
                'description' =>    "Global settings for the site, including registration and activation settings, site title, admin emails, and default languages.",
                'alerts' =>         $app->alerts->getAndClearMessages(), 
                'schema' =>         UF\PageSchema::load("default", $app->config('schema.path') . "/pages/pages.json"),
                'active_page' =>    ""
            ],
            'settings' => $app->site->getRegisteredSettings(),
            'info'     => $app->site->getSystemInfo(),
            'error_log'=> $app->site->getLog(50)
        ]);
    });   
    
    $app->post('/config/settings', function () use ($app) {
        // Get the alert message stream
        $ms = $app->alerts;
        
        $post = $app->request->post();
        
        // Remove CSRF token
        if (isset($post['csrf_token']))
            unset($post['csrf_token']);
            
        // Access-controlled page
        if (!$app->user->checkAccess('update_site_settings')){
            $ms->addMessageTranslated("danger", "ACCESS_DENIED");
            $app->halt(403);
        }
        
        // Hook for core and plugins to register their settings
        $app->applyHook("settings.register");
        
        // Get registered settings
        $registered_settings = $app->site->getRegisteredSettings();
        
        // Ok, check that all posted settings are registered
        foreach ($post as $plugin => $settings){
            if (!isset($registered_settings[$plugin])){
                $ms->addMessageTranslated("danger", "CONFIG_PLUGIN_INVALID", ["plugin" => $plugin]);
                $app->halt(400);
            }
            foreach ($settings as $name => $value){
                if (!isset($registered_settings[$plugin][$name])){
                    $ms->addMessageTranslated("danger", "CONFIG_SETTING_INVALID", ["plugin" => $plugin, "name" => $name]);
                    $app->halt(400);
                }
            }
        }
        
        // If validation passed, then update
        foreach ($post as $plugin => $settings){
            foreach ($settings as $name => $value){
                $app->site->set($plugin, $name, $value);
            }
        }
        $app->site->store();
        
    });
    
    // Slim info page
    $app->get('/sliminfo', function () use ($app) {
        // Access-controlled page
        if (!$app->user->checkAccess('uri_slim_info')){
            $app->notFound();
        }
        echo "<pre>";
        print_r($app->environment());
        echo "</pre>";
    });

    // PHP info page
    $app->get('/phpinfo', function () use ($app) {
        // Access-controlled page
        if (!$app->user->checkAccess('uri_php_info')){
            $app->notFound();
        }    
        echo "<pre>";
        print_r(phpinfo());
        echo "</pre>";
    });

    // PHP info page
    $app->get('/errorlog', function () use ($app) {
        // Access-controlled page
        if (!$app->user->checkAccess('uri_error_log')){
            $app->notFound();
        }
        $log = $app->site->getLog();
        echo "<pre>";
        echo implode("<br>",$log['messages']);
        echo "</pre>";
    });       
       
    $app->get('/test/auth', function() use ($app){
        $params = [
            "user" => [
                "id" => 1
            ],
            "post" => [
                "id" => 7
            ]
        ];
        
        $conditions = "(equals(self.id,user.id)||hasPost(self.id,post.id))&&subset(post, [\"id\", \"title\", \"content\", \"subject\", 3])";
        
        $ace = new UF\AccessConditionExpression($app);
        $result = $ace->evaluateCondition($conditions, $params);
        
        if ($result){
            echo "Passed $conditions";
        } else {
            echo "Failed $conditions";
        }
    });
    
    $app->run();
?>
