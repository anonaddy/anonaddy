import { WebAuthn } from './webauthn'

var errors = {
  key_already_used: "This key is already registered. It's not necessary to register it again.",
  key_not_allowed: 'The operation either timed out or was not allowed.',
  not_secured:
    "WebAuthn only supports secure connections. For testing over HTTP, you can use the origin 'localhost'.",
  not_supported: "Your browser doesn't currently support WebAuthn.",
}

let form = document.getElementById('form')

form.addEventListener('keypress', function (event) {
  if (event.key === 'Enter') {
    event.preventDefault()
    registerDevice()
  }
})

function errorMessage(name, message) {
  switch (name) {
    case 'InvalidStateError':
      return errors.key_already_used
    case 'NotAllowedError':
      return errors.key_not_allowed
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

function registerDevice() {
  document.getElementById('error').classList.add('hidden')

  if (document.getElementById('name').value === '') {
    return error('A device name is required')
  }

  if (document.getElementById('password').value === '') {
    return error('Your current password is required')
  }

  if (document.getElementById('name').value.length > 50) {
    return error('The device name may not be greater than 50 characters.')
  }

  webauthn.register(publicKey, function (data) {
    document.getElementById('id').value = data.id
    document.getElementById('rawId').value = data.rawId
    document.getElementById('attestationObject').value = data.response.attestationObject
    document.getElementById('clientDataJSON').value = data.response.clientDataJSON
    document.getElementById('type').value = data.type
    document.getElementById('form').submit()
  })
}

window.registerDevice = registerDevice
