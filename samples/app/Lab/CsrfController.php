<?php
namespace Lab;

use SampleI18n;
use SlightPHP\Csrf;
use SlightPHP\Request;
use SlightPHP\Response;
use SlightPHP\Route;
use SlightPHP\Tpl;

class CsrfController
{
    /**
     * 显示表单、Ajax 和 token 更新实验；Session 已由 samples 入口启动。
     * @param Request $request 当前请求。
     * @param Response $response 当前响应。
     * @return void
     */
    #[Route(method: 'GET')]
    public function Entry(Request $request, Response $response): void
    {
        $flash = $_SESSION['csrf_demo_flash'] ?? '';
        unset($_SESSION['csrf_demo_flash']);
        $code = <<<'PHP'
use SlightPHP\Csrf;
use SlightPHP\Request;
use SlightPHP\Response;
use SlightPHP\Route;

// The application starts the Session before dispatch.
$data['csrf'] = Csrf::token();
$data['csrf_input'] = Csrf::tokenInput();
// In Tpl: {$csrf_input|raw}

#[Route(method: 'POST', csrf: true)]
public function Submit(Request $request, Response $response): void
{
    // The dispatcher has validated _csrf or X-CSRF-Token.
    // Continue with authentication, permissions and business validation.
    $response->redirect('/lab/csrf/entry', 303);
}
PHP;
        (new Tpl())->render('workspace/csrf.tpl', [
            'view' => 'csrf',
            'navigation' => [
                'tasks' => ['/workspace/main/entry', '01', SampleI18n::t('task_workspace')],
                'route' => ['/lab/route/entry', '02', SampleI18n::t('route_lab')],
                'plugins' => ['/workspace/main/plugins', '03', SampleI18n::t('plugins_and_environment')],
                'template' => ['/lab/template/entry', '04', SampleI18n::t('templates_and_modules')],
                'language' => ['/lab/language/entry', '05', SampleI18n::t('language_example')],
                'csrf' => ['/lab/csrf/entry', '06', SampleI18n::t('csrf_demo_title')],
            ],
            'csrf' => Csrf::token(),
            'csrf_input' => Csrf::tokenInput(),
            'custom_csrf_input' => Csrf::tokenInput('--csrf--token'),
            'accepted' => (int) ($_SESSION['csrf_demo_accepted'] ?? 0),
            'flash' => in_array($flash, ['csrf_demo_form_saved', 'csrf_demo_rotated'], true) ? SampleI18n::t($flash) : '',
            'code' => $code,
            'form_code' => <<<'HTML'
<!-- token(): pass the value to Tpl and write the field yourself. -->
<input type="hidden" name="_csrf" value="{$csrf}">

<!-- tokenInput(): pass the generated HTML to Tpl. -->
{$csrf_input|raw}

<!-- Or call tokenInput() directly in Tpl. -->
{\SlightPHP\Csrf::tokenInput()|raw}
HTML,
            'direct_form_code' => <<<'HTML'
<form method="post" action="/lab/csrf/submit">
    {\SlightPHP\Csrf::tokenInput()|raw}
    <button type="submit">提交</button>
</form>
HTML,
            'custom_code' => <<<'PHP'
$data['csrf_input'] = Csrf::tokenInput('--csrf--token');
// In Tpl: {$csrf_input|raw}

#[Route(method: 'POST', csrf: '--csrf--token')]
public function CustomSubmit(Request $request, Response $response): void
{
    // Form field: --csrf--token
    // Or request header: --csrf--token (case-insensitive)
    $response->redirect('/lab/csrf/entry', 303);
}
PHP,
            'fetch_code' => <<<'JS'
const token = document.querySelector('[name="_csrf"]').value;
const response = await fetch('/lab/csrf/check', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': token
    },
    body: JSON.stringify({ example: 'csrf' })
});
// 200: validated; 403: missing, invalid or expired token.
// A rejection uses the application's HTML error page.
if (response.ok) console.log(await response.json());
else console.log(response.status);
JS,
        ]);
    }

    /**
     * 普通表单成功后计数并跳转；缺失或无效 token 无法进入本方法。
     * @param Request $request 当前表单请求。
     * @param Response $response 当前响应。
     * @return void
     */
    #[Route(method: 'POST', csrf: true)]
    public function Submit(Request $request, Response $response): void
    {
        $this->accept();
        $_SESSION['csrf_demo_flash'] = 'csrf_demo_form_saved';
        $response->redirect('/lab/csrf/entry', 303);
    }

    /**
     * 返回真实 JSON 响应；CSRF 由请求头提交，正文不参与 CSRF 校验。
     * @param Request $request 当前 JSON 请求。
     * @param Response $response 当前响应。
     * @return void
     */
    #[Route(method: 'POST', csrf: true)]
    public function Check(Request $request, Response $response): void
    {
        $response->header('Content-Type', 'application/json; charset=utf-8');
        $response->end(json_encode(['ok' => true, 'accepted' => $this->accept()], JSON_THROW_ON_ERROR));
    }

    /**
     * 演示自定义表单字段和请求头名称。
     * @param Request $request 当前表单请求。
     * @param Response $response 当前响应。
     * @return void
     */
    #[Route(method: 'POST', csrf: '--csrf--token')]
    public function CustomSubmit(Request $request, Response $response): void
    {
        $this->accept();
        $_SESSION['csrf_demo_flash'] = 'csrf_demo_form_saved';
        $response->redirect('/lab/csrf/entry', 303);
    }

    /**
     * 演示使用同名自定义请求头发送 JSON。
     * @param Request $request 当前 JSON 请求。
     * @param Response $response 当前响应。
     * @return void
     */
    #[Route(method: 'POST', csrf: '--csrf--token')]
    public function CustomCheck(Request $request, Response $response): void
    {
        $response->header('Content-Type', 'application/json; charset=utf-8');
        $response->end(json_encode(['ok' => true, 'accepted' => $this->accept()], JSON_THROW_ON_ERROR));
    }

    /**
     * 用当前有效 token 授权更新；旧页面的 token 随即失效。
     * @param Request $request 当前表单请求。
     * @param Response $response 当前响应。
     * @return void
     */
    #[Route(method: 'POST', csrf: true)]
    public function Rotate(Request $request, Response $response): void
    {
        Csrf::rotate();
        $_SESSION['csrf_demo_flash'] = 'csrf_demo_rotated';
        $response->redirect('/lab/csrf/entry', 303);
    }

    /** @return int 当前会话成功进入提交处理的次数，供页面观察被拒绝请求没有写入。 */
    private function accept(): int
    {
        return $_SESSION['csrf_demo_accepted'] = (int) ($_SESSION['csrf_demo_accepted'] ?? 0) + 1;
    }
}
