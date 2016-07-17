<?php
/**
 * author: shenzhe
 * Date: 13-6-17
 * route处理类
 */
namespace ZPHP\Core;
use ZPHP\Controller\IController,
    ZPHP\Core\Factory,
    ZPHP\Core\Config,
    ZPHP\ZPHP;
use ZPHP\Protocol\Request;
use ZPHP\Protocol\Response;
use ZPHP\Session\Swoole as SSESSION;

class Route
{
    public static function route()
    {
        $action = Config::get('ctrl_path', 'ctrl') . '\\' . Request::getCtrl();
        $class = Factory::getInstance($action);


        Log::write('action:'.$action);
        Log::write('$class:'.var_dump($class));
        try {

            if (!($class instanceof IController)) {
                throw new \Exception("ctrl error");
            } else {
                Log::write('main is ctrl');
                $view = null;
                if($class->_before()) {
                    $method = Request::getMethod();
                    Log::write('main is ctrl111'.$method);
                    if (!method_exists($class, $method)) {
                        throw new \Exception("method error");
                    }
                    $view = $class->$method();

                    Log::write('main is ctrl222');
                } else {

                    Log::write('main is ctrl3333');
                    throw new \Exception($action.':'.Request::getMethod().' _before() no return true');
                }
                $class->_after();
                if(Request::isLongServer()) {
                    SSESSION::save();
                }
                Log::write('view:'.json_encode($view));
                return json_encode($view);
            }
        }catch (\Exception $e) {
            Log::write('exception:'.$e->getMessage().'|'.$e->getFile().'|'.$e->getLine());
            if(Request::isLongServer()) {
                $result =  \call_user_func(Config::getField('project', 'exception_handler', 'ZPHP\ZPHP::exceptionHandler'), $e);
                if($class instanceof IController) {
                    $class->_after();
                }
                return $result;
            }
            if($class instanceof IController) {
                $class->_after();
            }
            throw $e;
        }
    }
}
