variable "hcloud_token" {
  description = "Hetzner Cloud API Token"
  type        = string
  sensitive   = true
}

variable "ssh_keys" {
  description = "Lista de SSH key names registradas en Hetzner"
  type        = list(string)
  default     = []
}
