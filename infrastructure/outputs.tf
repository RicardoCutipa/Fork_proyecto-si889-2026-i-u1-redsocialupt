output "server_ip" {
  description = "IP publica del servidor"
  value       = hcloud_server.web.ipv4_address
}

output "server_status" {
  description = "Estado del servidor"
  value       = hcloud_server.web.status
}

output "volume_id" {
  description = "ID del volumen de datos"
  value       = hcloud_volume.data.id
}
