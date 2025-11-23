<?php
namespace App\Core;

use App\Core\Auth;

class Router
{
    public function dispatch(): void
    {
        session_start();
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        if ($path === '/login') {
            (new \App\Controller\AuthController())->loginForm();
            return;
        }
        if ($path === '/login/submit' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            (new \App\Controller\AuthController())->login();
            return;
        }
        if ($path === '/logout') {
            (new \App\Controller\AuthController())->logout();
            return;
        }

        if (!Auth::check() && $path !== '/login') {
            header('Location: /login');
            return;
        }

        switch ($path) {
            case '/':
                (new \App\Controller\DashboardController())->index();
                break;

            case '/employees':
                (new \App\Controller\EmployeeController())->index();
                break;
            case '/employees/create':
                $ctrl = new \App\Controller\EmployeeController();
                if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') $ctrl->create(); else $ctrl->createForm();
                break;
            case '/employees/edit':
                $ctrl = new \App\Controller\EmployeeController();
                if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') $ctrl->edit(); else $ctrl->editForm();
                break;
            case '/employees/delete':
                (new \App\Controller\EmployeeController())->delete();
                break;

            case '/categories':
                (new \App\Controller\CategoryController())->index();
                break;
            case '/categories/create':
                (new \App\Controller\CategoryController())->create();
                break;
            case '/categories/edit':
                (new \App\Controller\CategoryController())->edit();
                break;
            case '/categories/delete':
                (new \App\Controller\CategoryController())->delete();
                break;

            case '/customers':
                (new \App\Controller\CustomerController())->index();
                break;
            case '/customers/create':
                (new \App\Controller\CustomerController())->create();
                break;
            case '/customers/edit':
                (new \App\Controller\CustomerController())->edit();
                break;
            case '/customers/delete':
                (new \App\Controller\CustomerController())->delete();
                break;
            case '/customers/profile':
                (new \App\Controller\CustomerController())->profile();
                break;

            case '/contracts':
                (new \App\Controller\ContractsController())->index();
                break;
            case '/contracts/create':
                $ctrl = new \App\Controller\ContractsController();
                if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') $ctrl->create(); else header('Location: /contracts');
                break;
            case '/contracts/edit':
                (new \App\Controller\ContractsController())->edit();
                break;
            case '/contracts/delete':
                (new \App\Controller\ContractsController())->delete();
                break;

            case '/payments':
                (new \App\Controller\PaymentsController())->index();
                break;
            case '/payments/create':
                (new \App\Controller\PaymentsController())->create();
                break;
            case '/payments/edit':
                (new \App\Controller\PaymentsController())->edit();
                break;
            case '/payments/delete':
                (new \App\Controller\PaymentsController())->delete();
                break;
            case '/payments/contract-info':
                (new \App\Controller\PaymentsController())->contractInfo();
                break;

            case '/expenses':
                (new \App\Controller\ExpensesController())->index();
                break;
            case '/expense-categories':
                (new \App\Controller\ExpenseCategoriesController())->index();
                break;
            case '/expense-categories/create':
                (new \App\Controller\ExpenseCategoriesController())->create();
                break;
            case '/expense-categories/edit':
                (new \App\Controller\ExpenseCategoriesController())->edit();
                break;
            case '/expense-categories/delete':
                (new \App\Controller\ExpenseCategoriesController())->delete();
                break;
            case '/expenses/create':
                (new \App\Controller\ExpensesController())->create();
                break;
            case '/expenses/edit':
                (new \App\Controller\ExpensesController())->edit();
                break;
            case '/expenses/delete':
                (new \App\Controller\ExpensesController())->delete();
                break;

            case '/products':
                (new \App\Controller\ProductsController())->index();
                break;
            case '/products/store':
                (new \App\Controller\ProductsController())->store();
                break;
            case '/products/update':
                (new \App\Controller\ProductsController())->update();
                break;
            case '/products/delete':
                (new \App\Controller\ProductsController())->delete();
                break;

            case '/services':
                (new \App\Controller\ServicesController())->index();
                break;
            case '/services/store':
                (new \App\Controller\ServicesController())->store();
                break;
            case '/services/update':
                (new \App\Controller\ServicesController())->update();
                break;
            case '/services/delete':
                (new \App\Controller\ServicesController())->delete();
                break;

            case '/servers':
                (new \App\Controller\ServersController())->index();
                break;
            case '/servers/delete':
                (new \App\Controller\ServersController())->delete();
                break;
            case '/servers/check':
                (new \App\Controller\ServersController())->check();
                break;
            case '/servers/sync-hosting':
                (new \App\Controller\ServersController())->syncHosting();
                break;

            case '/directadmin/accounts/create':
                (new \App\Controller\DirectAdminController())->create();
                break;
            case '/directadmin/accounts/suspend':
                (new \App\Controller\DirectAdminController())->suspend();
                break;
            case '/directadmin/accounts/unsuspend':
                (new \App\Controller\DirectAdminController())->unsuspend();
                break;
            case '/directadmin/accounts/delete':
                (new \App\Controller\DirectAdminController())->delete();
                break;
            case '/directadmin/accounts/sync':
                (new \App\Controller\DirectAdminController())->sync();
                break;

            case '/domains/sync':
                (new \App\Controller\DomainController())->sync();
                break;
            case '/domains/register':
                (new \App\Controller\DomainController())->register();
                break;
            case '/domains/suspend':
                (new \App\Controller\DomainController())->suspend();
                break;
            case '/domains/unsuspend':
                (new \App\Controller\DomainController())->unsuspend();
                break;
            case '/domains/delete':
                (new \App\Controller\DomainController())->delete();
                break;
            case '/domains/transfer':
                (new \App\Controller\DomainController())->transfer();
                break;
            case '/domains/renew':
                (new \App\Controller\DomainController())->renew();
                break;
            case '/domains/dns':
                (new \App\Controller\DomainController())->dns();
                break;
            case '/domains/dns/delete':
                (new \App\Controller\DomainController())->dnsDelete();
                break;
            case '/domains/whois':
                (new \App\Controller\DomainController())->whois();
                break;
            case '/domains/reconcile':
                (new \App\Controller\DomainController())->reconcile();
                break;

            case '/payroll':
                (new \App\Controller\PayrollController())->index();
                break;
            case '/payroll/create':
                $ctrl = new \App\Controller\PayrollController();
                if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') $ctrl->create(); else $ctrl->createForm();
                break;
            case '/payroll/delete':
                (new \App\Controller\PayrollController())->delete();
                break;

            case '/reports':
                (new \App\Controller\ReportsController())->index();
                break;

            case '/misc-sites':
                (new \App\Controller\MiscSitesController())->index();
                break;

            case '/json/directadmin/reseller-config.json':
                (new \App\Controller\JsonController())->directAdminConfig();
                break;
            case '/json/directadmin/swagger.json':
                (new \App\Controller\JsonController())->directAdminSwagger();
                break;
            case '/json/directadmin/directadmin_reseller_api_map.json':
                (new \App\Controller\JsonController())->directAdminApiMap();
                break;
            case '/json/domin/openapi (1).json':
                (new \App\Controller\JsonController())->domainOpenApi();
                break;

            default:
                http_response_code(404);
                echo "404 Not Found";
        }
    }
}