variable "PHP_VERSION" {
  default = null
}
variable "COMPOSER_VERSION" {
  default = null
}
variable "NODE_VERSION" {
  default = null
}

group "default" {
  targets = ["validate"]
}

group "validate" {
  targets = ["composer-validate", "npm-validate", "phpunit"]
}

group "test" {
  targets = ["phpunit"]
}

target "_common" {
  dockerfile = "dev.Dockerfile"
  args = {
    PHP_VERSION = PHP_VERSION
    COMPOSER_VERSION = COMPOSER_VERSION
    NODE_VERSION = NODE_VERSION
  }
}

target "composer-validate" {
  inherits = ["_common"]
  target = "composer-validate"
  output = ["type=cacheonly"]
}

target "npm-validate" {
  inherits = ["_common"]
  target = "npm-validate"
  output = ["type=cacheonly"]
}

target "phpunit" {
  inherits = ["_common"]
  target = "phpunit"
  output = ["type=cacheonly"]
}

group "update-locks" {
  targets = ["composer-lock", "npm-lock"]
}

target "composer-lock" {
  inherits = ["_common"]
  target = "composer-lock"
  output = ["type=local,dest=."]
}

target "npm-lock" {
  inherits = ["_common"]
  target = "npm-lock"
  output = ["type=local,dest=."]
}
