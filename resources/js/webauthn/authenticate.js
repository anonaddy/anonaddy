import { WebAuthn } from './webauthn'

var errors = {
  key_not_allowed: 'The operation either timed out or was not allowed.',
  not_secured:
    "WebAuthn only supports secure connections. For testing over HTTP, you can use the origin 'localhost'.",
  not_supported: "Your browser doesn't currently support WebAuthn.",
  wrong_key: 'The key is either incorrect or has been disabled in your account.',
}

function errorMessage(name, message) {
  switch (name) {
    case 'InvalidStateError':
      return errors.key_not_allowed
    case 'NotAllowedError':
      return errors.wrong_key
    default:
      return message
  }
}

function error(message) {
  document.getElementById('error').innerHTML = message
  document.getElementById('error').classList.remove('hidden')
}

var webauthn = new WebAuthn((name, message) => {
  error(errorMessage(name, message))
})

if (!webauthn.webAuthnSupport()) {
  switch (webauthn.notSupportedMessage()) {
    case 'not_secured':
      error(errors.not_secured)
      break
    case 'not_supported':
      error(errors.not_supported)
      break
  }
}

if (!/apple/i.test(navigator.vendor)) {
  webauthn.sign(publicKey, function (data) {
    document.getElementById('success').classList.remove('hidden')
    document.getElementById('id').value = data.id
    document.getElementById('rawId').value = data.rawId
    document.getElementById('authenticatorData').value = data.response.authenticatorData
    document.getElementById('clientDataJSON').value = data.response.clientDataJSON
    document.getElementById('signature').value = data.response.signature
    document.getElementById('userHandle').value = data.response.userHandle
    document.getElementById('type').value = data.type
    document.getElementById('form').submit()
  })
}

function authenticateDevice() {
  document.getElementById('error').classList.add('hidden')
  webauthn.sign(publicKey, function (data) {
    document.getElementById('success').classList.remove('hidden')
    document.getElementById('id').value = data.id
    document.getElementById('rawId').value = data.rawId
    document.getElementById('authenticatorData').value = data.response.authenticatorData
    document.getElementById('clientDataJSON').value = data.response.clientDataJSON
    document.getElementById('signature').value = data.response.signature
    document.getElementById('userHandle').value = data.response.userHandle
    document.getElementById('type').value = data.type
    document.getElementById('form').submit()
  })
}

window.authenticateDevice = authenticateDevice
