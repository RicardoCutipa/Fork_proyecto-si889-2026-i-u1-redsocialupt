terraform {
  required_providers {
    hcloud = {
      source  = "hetznercloud/hcloud"
      version = "~> 1.45"
    }
  }
}

provider "hcloud" {
  token = var.hcloud_token
}

# ── Servidor principal ────────────────────────────────────────────────
resource "hcloud_server" "web" {
  name        = "redsocial-upt"
  server_type = "cx22"
  image       = "debian-12"
  location    = "nbg1"

  ssh_keys = var.ssh_keys

  user_data = <<-EOF
    #!/bin/bash
    apt-get update
    apt-get install -y docker.io docker-compose-plugin
    systemctl enable docker
    systemctl start docker
  EOF

  labels = {
    project = "redsocial-upt"
    env     = "production"
  }
}

# ── Volumen persistente para MySQL ────────────────────────────────────
resource "hcloud_volume" "data" {
  name     = "redsocial-data"
  size     = 20
  location = "nbg1"

  labels = {
    project = "redsocial-upt"
  }
}

resource "hcloud_volume_attachment" "data_attach" {
  volume_id = hcloud_volume.data.id
  server_id = hcloud_server.web.id
}

# ── Firewall ──────────────────────────────────────────────────────────
resource "hcloud_firewall" "web" {
  name = "redsocial-firewall"

  rule {
    direction = "in"
    protocol  = "tcp"
    port      = "22"
    source_ips = ["0.0.0.0/0", "::/0"]
  }

  rule {
    direction = "in"
    protocol  = "tcp"
    port      = "80"
    source_ips = ["0.0.0.0/0", "::/0"]
  }

  rule {
    direction = "in"
    protocol  = "tcp"
    port      = "443"
    source_ips = ["0.0.0.0/0", "::/0"]
  }
}

resource "hcloud_firewall_attachment" "web" {
  firewall_id = hcloud_firewall.web.id
  server_ids  = [hcloud_server.web.id]
}
