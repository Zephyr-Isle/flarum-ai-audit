#### 2026-07-05
ClassmateHub is a Flutter app designed to adapt to Flarum forum software and its PHP extensions

#### 2026-07-05
EXTENSIONS.md documents which Flarum extensions are supported, including core features like flarum/likes (Likes) which is fully adapted

#### 2026-07-05
API services follow a service-based pattern with separate files for each domain: api_client.dart, auth_service.dart, discussion_service.dart, forum_service.dart, group_service.dart, notification_service.dart, post_service.dart, tag_service.dart, user_service.dart

#### 2026-07-05
User model handles multiple attribute name variations for compatibility (coverUrl, cover, profileCover) when parsing API responses

#### 2026-07-05
LoginRequest uses 'identification' field instead of traditional username/email for authentication

#### 2026-07-09
Project uses Flarum v2 declarative Extender API instead of v1 imperative API, as documented in REFACTORING.md.

#### 2026-07-09
Webpack config defines admin module path as './src/admin/index.js' but the actual source file is 'index.ts'.

#### 2026-07-09
Admin settings labels are externalized to YAML translation files using keys under 'zephyrisle-ai-audit.admin.settings'.

#### 2026-07-09
Current focus is debugging backend settings not displaying in Flarum v2 admin panel.