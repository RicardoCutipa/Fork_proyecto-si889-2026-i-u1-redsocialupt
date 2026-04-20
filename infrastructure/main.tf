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

resource "hcloud_server" "web" {
  name        = "redsocial-upt"
  server_type = "cx22"
  image       = "debian-12"
  location    = "nbg1"

  labels = {
    project = "redsocial-upt"
    env     = "production"
  }
}

resource "hcloud_volume" "data" {
  name     = "redsocial-data"
  size     = 20
  location = "nbg1"
}

resource "hcloud_volume_attachment" "data_attach" {
  volume_id = hcloud_volume.data.id
  server_id = hcloud_server.web.id
}
