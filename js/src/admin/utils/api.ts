import app from 'flarum/admin/app';

type JsonApiError = {
  detail?: string;
  title?: string;
};

type ErrorLike = {
  message?: string;
  responseText?: string;
  response?: {
    errors?: JsonApiError[];
  };
  responseJSON?: {
    errors?: JsonApiError[];
  };
};

export function apiUrl(path: string): string {
  return `${app.forum.attribute<string>('apiUrl')}${path}`;
}

export function showRequestError(error: unknown, fallbackKey: string): void {
  const message = extractErrorMessage(error) || app.translator.trans(fallbackKey);

  app.alerts.show({ type: 'error' }, message);
}

function extractErrorMessage(error: unknown): string | null {
  const normalized = error as ErrorLike | undefined;
  const apiMessage =
    normalized?.response?.errors?.find((item) => item.detail || item.title) ||
    normalized?.responseJSON?.errors?.find((item) => item.detail || item.title);

  if (apiMessage?.detail) return apiMessage.detail;
  if (apiMessage?.title) return apiMessage.title;
  if (normalized?.message) return normalized.message;
  if (normalized?.responseText) return normalized.responseText;

  return null;
}
