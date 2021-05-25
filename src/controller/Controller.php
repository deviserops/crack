<?php

namespace src\controller;

class Controller {

    protected function view($viewName, $params = []) {
        if (count($params)) {
            extract($params);
        }
        include __DIR__ . '/../views/' . $viewName . '.php';
    }

}
