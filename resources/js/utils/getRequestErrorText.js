export const RATE_LIMIT_MESSAGE = 'Too many requests, please try again in a couple of minutes'

export const BULK_RATE_LIMIT_MESSAGE =
  'Too many bulk requests, please wait a little while before trying again'

export function getRequestErrorText(error, rateLimitMessage = RATE_LIMIT_MESSAGE) {
  if (error.response?.status === 429) {
    return rateLimitMessage
  }

  if (typeof error.response?.data === 'string') {
    return error.response.data
  }

  if (typeof error.response?.data?.message === 'string') {
    return error.response.data.message
  }
}
