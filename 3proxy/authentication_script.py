#!/usr/bin/env python
import sys
from pyrad.client import Client
from pyrad.packet import AccessRequest, AccessAccept, AccessReject
from pyrad.dictionary import Dictionary
import os

username = sys.stdin.readline().strip()  # Đọc từ 3proxy
password = sys.stdin.readline().strip()  # Đọc từ 3proxy

RADIUS_SERVER = "192.168.100.254"
RADIUS_SECRET = b"testing123"
RADIUS_PORT = 1812

client = Client(server=RADIUS_SERVER, secret=RADIUS_SECRET, dict=Dictionary("/etc/freeradius/dictionary"))
client.AuthPort = RADIUS_PORT

req = client.CreateAuthPacket(code=AccessRequest, user=username)
req["User-Name"] = req.AddString(username)
req["User-Password"] = req.AddString(password)

try:
    reply = client.SendPacket(req)
    if reply.code == AccessAccept:
        sys.stdout.write("OK\n")
    else:
        sys.stdout.write("ERR\n")
except Exception as e:
    sys.stderr.write(f"Error: {e}\n")
    sys.exit(1)
