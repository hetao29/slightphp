# 完整示例说明

启动方法见 [快速开始](getting-started.md)。默认首页为框架介绍官网，详见 [框架官网说明](website.md)。从页脚进入任务工作台后，可按「任务工作台 → 路由实验室 → 插件与环境」体验框架。

## 示例功能

- 任务列表包含初始示例任务，可新增、切换完成状态和删除。
- 按全部、待完成、已完成筛选，可同时按标题搜索；无匹配结果时显示空状态。
- 列表显示总数、待完成数量和完成进度。
- 提交成功或校验失败后显示提示；POST 返回 303，刷新不会重复提交。
- 页面支持窄屏、键盘焦点、输入标签、当前导航、状态播报和跳转到正文。
- JavaScript 负责搜索筛选、删除确认及路由实验输入跳转；任务增删和完成切换不依赖 JavaScript，关闭 JavaScript 后删除会直接提交。

## 路由与职责

以下为从网站根目录访问的路径：

| 路径 | HTTP 方法 | 行为 |
| --- | --- | --- |
| `/workspace/main/entry` | GET | 显示会话任务列表 |
| `/workspace/main/save` | POST | `action=add/toggle/delete`，需要 CSRF token |
| `/workspace/main/api` | GET | 当前会话任务 JSON，含 data 和 meta |
| `/lab/route/entry` | GET | 命名空间控制器与实际路径数组 |
| `/lab/route/entry/hello` | GET | 演示附加路径参数 |
| `/workspace/main/plugins` | GET | 真实解析 SConfig 配置及检测扩展 |
| 不存在的控制器或方法 | 任意 | HTTP 404 和可返回首页的错误页 |

`save` 的错误 HTTP 方法返回 405、CSRF 失败返回 403；业务输入错误通过跳转后的提示呈现。API 的非 GET 请求返回 405 JSON。

## 数据与安全设计

入口显式选择 files 会话后端并使用系统临时目录，避免依赖机器默认的 Redis 会话配置。数据保存在 PHP session 中，首次访问初始化三条任务。不同会话相互隔离；同一会话的 JSON 接口与页面共享数据。关闭浏览器、会话清理等可能导致数据丢失，不承诺持久存储。

任务标题要求 1–80 个 Unicode 字符，每会话最多 50 项；浏览器校验之外还有服务端校验。所有动态 HTML 使用 `htmlspecialchars` 转义，写操作需要 CSRF token。不要将其当作带用户体系的生产任务系统：没有登录、跨设备同步、持久数据库或权限管理。

## 验证

部署示例站点后，输入站点地址运行 HTTP 验证：

```bash
read -r -p "站点地址（包含 http:// 或 https://）: " SITE_URL
python3 samples/tests/smoke.py "$SITE_URL"
```

覆盖会话隔离、添加、切换、删除、空标题、超长标题、CSRF、方法限制、JSON、转义、404 和命名空间参数。手动验证：缩小至手机宽度，使用 Tab 操作表单与任务按钮，组合搜索和筛选，取消一次删除，然后刷新确认不会重复新增。

## 清理后的范围

samples 仅包含官网与当前可运行的任务、路由、配置和 JSON 示例。历史 user、photos、guestbook、Auction、数据库与 Redis 测试、backend、locale、旧配置和模板缓存已删除。插件实现仍保留在仓库 plugins 中，可参考插件文档独立使用。
