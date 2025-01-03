start_ip = 245
end_ip = 246

with open('docker-compose.yml', 'w') as f:
    f.write("version: '3.8'\n\nservices:\n")
    for i in range(start_ip, end_ip + 1):
        container_name = f"3proxy_100_{i}"
        ip_address = f"192.168.100.{i}"
        f.write(f"""
  {container_name.replace('[', '').replace(']', '')}:
    image: tiensy94/3proxy:311224
    container_name: {container_name}
    privileged: true
    networks:
      ipvlan_network:
        ipv4_address: {ip_address}
    environment:
      - TZ=Asia/Ho_Chi_Minh
    volumes:
      - /home/sytt/project-proxy/3proxy/3proxy.cfg:/usr/local/3proxy/conf/3proxy.cfg
      - /home/sytt/project-proxy/log:/usr/local/3proxy/logs
    command: ["sh", "-c", "service 3proxy start && service haproxy start  && sleep infinity"]
""")
    f.write("\nnetworks:\n  ipvlan_network:\n    external: true\n    name: ipvlan_network\n")
#f.write("\nnetworks:\n  ipvlan_network:\n    external: true\n") 
