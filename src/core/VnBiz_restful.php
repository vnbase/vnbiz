<?php
// new trait named Model_permission
namespace VnBiz;

use Exception, R;
use RedBeanPHP\RedException\SQL as SQLException;
use SimpleXMLElement;
use VnBiz\Model;
use VnBiz_sql;

trait VnBiz_restful
{

    public function restful()
    {
        // Specify domains from which requests are allowed
        header('Access-Control-Allow-Origin: *');

        // Specify which request methods are allowed
        header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');

        // Additional headers which may be sent along with the CORS request
        header('Access-Control-Allow-Headers: *');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            // Set the age to 1 day to improve speed/caching.
            header('Access-Control-Max-Age: 86400');
            return;
        }

        $context = array_merge($_GET, $_POST);
        $json = json_decode(file_get_contents('php://input'), true);

        if ($json) {
            $context = array_merge($context, $json);
        }

        if (isset($_FILES['model'])) {
            isset($context['model']) ?: $context['model'] = [];
            foreach ($_FILES['model']['name'] as $field_name => $file_name) {
                if (is_string($file_name)) {
                    if ($_FILES['model']['error'][$field_name] === 0) {
                        $context['model'][$field_name] = [
                            'file_name' => $file_name,
                            'file_type' => $_FILES['model']['type'][$field_name],
                            'file_size' => $_FILES['model']['size'][$field_name],
                            'file_path' => $_FILES['model']['tmp_name'][$field_name]
                        ];
                    }
                } else {
                    throw new VnBizError('Unsupported multiple file upload', 'invalid_model');
                }
            }
        }

        $result = [
            'code' => 'no_such_action'
        ];


        try {
            vnbiz_do_action('web_before', $context);

            $action = vnbiz_get_var($context['action'], '');
            switch ($action) {
                case 'model_create':
                    vnbiz_do_action('web_model_create', $context);
                    $result = $context;
                    $result['code'] = 'success';
                    break;
                case 'model_update':
                    vnbiz_do_action('web_model_update', $context);
                    $result = $context;
                    $result['code'] = 'success';
                    break;
                case 'model_count':
                    // throw new VnBizError("Operator is not supported", "unsupported");
                    vnbiz_do_action('web_model_count', $context);
                    $result = $context;
                    $result['code'] = 'success';
                    break;
                case 'model_find':
                    vnbiz_do_action('web_model_find', $context);
                    $result = $context;
                    $result['code'] = 'success';
                    break;
                case 'model_delete':
                    vnbiz_do_action('web_model_delete', $context);
                    $result = $context;
                    $result['code'] = 'success';
                    break;
                default:
                    if (vnbiz_str_starts_with($action, 'service_')) {
                        if (!vnbiz_has_action($action)) {
                            throw new VnBizError("No such service", "service_not_found");
                        }

                        vnbiz_do_action($action, $context);

                        if (isset($context['models'])) {
                            $arr = [];
                            foreach ($context['models'] as &$model) {
                                if (isset($model['@model_name'])) {
                                    $arr[$model['@model_name']] = $arr[$model['@model_name']] ?? [];
                                    $arr[$model['@model_name']][] = &$model;
                                }
                            }
                            foreach ($arr as $model_name => $data) {
                                $c = ['models' => &$data];
                                vnbiz_do_action("web_after_model_find_$model_name", $c);
                            }
                        }
                        $result = $context;
                        if (!isset($result['code'])) {
                            throw new VnBizError("Service $action doesn't have code in response", 'system');
                        }
                        if ($result['code'] !== 'success') {
                            http_response_code(400);
                        }
                    } else {
                        $result['message'] = "No action name '$action'";
                    }
            }
        } catch (VnbizError $e) {
            http_response_code($e->http_status());
            echo json_encode($e);

            $result = [
                'code' => $e->get_status(),
                'error' => $e->getMessage(),
                'error_fields' => $e->get_error_fields(),
                'stack' => $e->getTraceAsString()
            ];
        } catch (Exception $e) {
            L()->error($e->getMessage(), [
                'context' => $context,
                'stack' => $e->getTraceAsString()
            ]);

            http_response_code(500);
            $result = [
                'code' => 'error',
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
            ];
        };


        vnbiz_do_action('web_after', $context);
        return $result;
    }

    public function handle_restful()
    {
        ob_start();
        $result = $this->restful();
        $output = ob_get_clean();
        if ($output) {
            error_log($output);
        }

        if (!vnbiz_debug_enabled()) {
            unset($result['params']);
            unset($result['sql_query_conditions']);
            unset($result['sql_query_params']);
            unset($result['sql_lock_query']);
            unset($result['sql_query']);
            unset($result['sql_params']);
            unset($result['sql_query_order']);
            unset($result['stack']);
        }

        header('Content-Type: application/json');
        echo json_encode($result, JSON_UNESCAPED_SLASHES);
        return;
    }

    public function handle_restful_xml()
    {
        ob_start();
        $result = $this->restful();
        $output = ob_get_clean();
        if ($output) {
            error_log($output);
        }
        unset($result['params']);

        $xml = new SimpleXMLElement('<?xml version="1.0"?><data></data>');
        vnbiz_array_to_xml($result, $xml);
        header("Content-type: text/xml; charset=utf-8");
        echo $xml->asXML();
        return;
    }
}
