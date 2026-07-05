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