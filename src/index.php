<?php

use src\controller\Controller;

class crack extends Controller {

    public static $projectName = '';
    public static $assetDir = '/assets/';

    public function run() {
        $getUrl = $this->checkServer();
        $cleanUrl = $this->getCleanUrl($getUrl);

        $routes = include 'app/routes.php';

        if (!isset($routes[$cleanUrl])) {
            /**
             * Add upper function to manipulate route param
             */
            list($routes, $params) = $this->getUpdateRoutes($routes, $cleanUrl);
            if (!isset($routes[$cleanUrl])) {
                return $this->view('notFound');
                /**
                 * custom not found page
                 */
            } else {
                return $this->getFunctionality($routes[$cleanUrl], $params);
            }
        } else {
            return $this->getFunctionality($routes[$cleanUrl]);
        }
    }

    private function getFunctionality($functionality, $params = []) {

        $classAndFunction = explode('@', $functionality);

        $class = $classAndFunction[0];
        $function = $classAndFunction[1];
        /**
         * Manually load classes
         */
//        include_once __DIR__ . '/controllers/' . $class . '.php';

        $className = '\app\controllers\\' . $class;

        $obj = new $className;
        return call_user_func_array([$obj, $function], $params);
    }

    private function getCleanUrl($url) {
        $url = explode('?', $url);
        $onlyUrl = $url[0];
        $urlArray = str_split($onlyUrl);

        /** If remove last url then the default index function will never call from controller **/
//        if (end($urlArray) == '/') {
//            $keys = count($urlArray) - 1;
//            unset($urlArray[$keys]);
//        }
        $cleanUrl = implode('', $urlArray);
        return $cleanUrl;
        /**
         * Remove all after ? and last / in url remaining
         */
    }

    public function checkServer() {
        $project = rtrim(str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']), '/');
        /** Set Project Name globally for path use **/
        self::$projectName = $project;

        $requestUri = $_SERVER['REQUEST_URI'];          //      /php/home
        return str_replace($project, '', $requestUri);

    }

    private function getUpdateRoutes($routes, $getCleanUrl) {
        //manipulate route param


        foreach ($routes as $keyUrl => $function) {
            $params = [];
            $urlSize = explode('/', $getCleanUrl);
            $keySize = explode('/', $keyUrl);
            $requireParam = false;
            $optionalParam = false;

            /**
             * check if both optional and required are available
             */
            $urlDiff = array_diff($keySize, $urlSize);

            foreach ($keySize as $ks => $kv) {
                if (isset($urlSize[$ks]) && ($kv == $urlSize[$ks])) {

                } elseif (strpos($kv, '?}')) {
                    if ($optionalParam) {
                        continue;
                    }
                    $optionalParam = true;
                } elseif (strpos($kv, '}')) {
                    if ($requireParam) {
                        continue;
                    }
                    $requireParam = true;
                }
            }

            if (($requireParam == true) && ($optionalParam == true)) {
                $getParams = [];
                $urlSize = $urlSize;
                $keySize = $keySize;
                $newReqSize = 0;
                $updateUrl = true;
                foreach ($keySize as $ks => $kv) {
                    if (isset($urlSize[$ks]) && ($kv == $urlSize[$ks])) {
                        $newReqSize++;
                    } elseif (strpos($kv, '?}')) {
                        /**
                         * Add Params
                         */
                        if (isset($urlSize[$ks]) && !empty($urlSize[$ks])) {
                            $getParams[] = $urlSize[$ks];
                        }
                        /**
                         * Add Params
                         */
                    } elseif (strpos($kv, '}')) {
                        if (isset($urlSize[$ks]) && !empty($urlSize[$ks])) {
                            $getParams[] = $urlSize[$ks];
                        }
                        $newReqSize++;
                    }
                    if (!strpos($kv, '}') && isset($urlSize[$ks]) && ($kv != $urlSize[$ks])) {
                        $updateUrl = false;
                    }
                }
                if ($updateUrl && (count($urlSize) >= $newReqSize) && (count($urlSize) <= count($keySize))) {
                    unset($routes[$keyUrl]);
                    $keyUrl = implode('/', $urlSize);
                    $routes[$keyUrl] = $function;
                    $params = $getParams;
                    break;
                }
            } elseif (strpos($keyUrl, "?}")) {
                $getParams = [];
                $update = true;
                foreach ($urlDiff as $key => $diffVal) {
                    if (!strpos($diffVal, '?}')) {
                        $update = false;
                    }
                }
                /**
                 * Add Param only
                 *
                 */
                foreach ($keySize as $ks => $kv) {
                    if (strpos($kv, '?}')) {
                        if (isset($urlSize[$ks]) && !empty($urlSize[$ks])) {
                            $getParams[] = $urlSize[$ks];
                        }
                    }
                }
                /**
                 * Add Param only
                 *
                 */
                /**
                 * update url
                 */
                if ($update && (count($urlSize) <= count($keySize))) {
                    unset($routes[$keyUrl]);
                    $keyUrl = implode('/', $urlSize);
                    $routes[$keyUrl] = $function;
                    $params = $getParams;
                    break;
                }
            } elseif (strpos($keyUrl, "}")) {
                $getParams = [];
                /**
                 * Add Param only
                 *
                 */
                $continue = false;
                foreach ($keySize as $ks => $kv) {
                    if (strpos($kv, '}')) {
                        if (isset($urlSize[$ks]) && !empty($urlSize[$ks])) {
                            $getParams[] = $urlSize[$ks];
                        }
                    }
                    if (!strpos($kv, '}')) {
                        if (isset($urlSize[$ks]) && $kv != $urlSize[$ks]) {
                            $continue = true;
                        }
                    }
                }
                if ($continue) {
                    continue;
                }
                /**
                 * Add Param only
                 *
                 */
                if (count($urlSize) != count($keySize)) {
                    continue;
                }
                $update = false;
                foreach ($urlSize as $urlKey => $urlVal) {
                    if ($urlVal != $keySize[$urlKey]) {
                        $keySize[$urlKey] = $urlVal;
                        $update = true;
                    }
                }
                /**
                 * update url
                 */
                if ($update) {
                    unset($routes[$keyUrl]);
                    $keyUrl = implode('/', $keySize);
                    $routes[$keyUrl] = $function;
                    $params = $getParams;
                    break;
                }
            }
        }
        return [$routes, $params];
    }

    public static function asset($path = '') {
        return $_SERVER['REQUEST_SCHEME'] . '://' . rtrim($_SERVER['SERVER_NAME'], '/') . self::$projectName . self::$assetDir . ltrim($path, '/');
    }

    public static function assetRoot($path = '') {
        return rtrim($_SERVER['DOCUMENT_ROOT'], '/') . self::$projectName . self::$assetDir . ltrim($path, '/');
    }

}