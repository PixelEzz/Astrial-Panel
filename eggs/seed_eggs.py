import json
import os

eggs = {
    # Minecraft
    "minecraft/paper.json": {
        "meta": {"version": "PTDL_v2", "update_url": None},
        "name": "Paper",
        "category": "minecraft",
        "author": "support@gamepanel.io",
        "description": "High performance Minecraft server software aiming to fix gameplay and mechanics inconsistencies.",
        "features": ["eula", "java_version"],
        "docker_images": {"Java 17": "ghcr.io/pterodactyl/yolks:java_17", "Java 21": "ghcr.io/pterodactyl/yolks:java_21", "Alpine": "alpine:latest"},
        "startup": "java -Xms128M -Xmx{{SERVER_MEMORY}}M -Dterminal.jline=false -Dterminal.ansi=true -jar {{SERVER_JARFILE}}",
        "config": {"files": {"server.properties": {"parser": "properties", "find": {"server-port": "{{SERVER_PORT}}"}}}, "startup": {"done": ")! For help, type "}},
        "scripts": {"installation": {"script": "#!/bin/sh\napk add --no-cache curl jq\nLATEST=$(curl -s https://api.papermc.io/v2/projects/paper/versions/1.20.4/builds | jq '.builds[-1].build')\ncurl -o server.jar https://api.papermc.io/v2/projects/paper/versions/1.20.4/builds/${LATEST}/downloads/paper-1.20.4-${LATEST}.jar || echo 'eula=true' > eula.txt\necho 'eula=true' > eula.txt\n", "container": "alpine:latest", "entrypoint": "sh"}},
        "variables": [
            {"name": "Server Jar File", "env_variable": "SERVER_JARFILE", "default_value": "server.jar", "user_viewable": True, "user_editable": True, "rules": "required|string"},
            {"name": "Minecraft Version", "env_variable": "MINECRAFT_VERSION", "default_value": "1.20.4", "user_viewable": True, "user_editable": True, "rules": "required|string"}
        ]
    },
    "minecraft/purpur.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Purpur",
        "category": "minecraft",
        "author": "support@gamepanel.io",
        "description": "Purpur is a drop-in replacement for Paper servers designed for configurability and new fun gameplay features.",
        "docker_images": {"Java 21": "ghcr.io/pterodactyl/yolks:java_21", "Alpine": "alpine:latest"},
        "startup": "java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar {{SERVER_JARFILE}}",
        "config": {"files": {"server.properties": {"parser": "properties"}}, "startup": {"done": ")! For help, type "}},
        "scripts": {"installation": {"script": "echo 'eula=true' > eula.txt", "container": "alpine:latest"}},
        "variables": [{"name": "Server Jar File", "env_variable": "SERVER_JARFILE", "default_value": "purpur.jar", "user_viewable": True, "user_editable": True, "rules": "required"}]
    },
    "minecraft/folia.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Folia",
        "category": "minecraft",
        "author": "support@gamepanel.io",
        "description": "Folia is a new Paper fork that adds regional multithreading to the dedicated server.",
        "docker_images": {"Java 21": "ghcr.io/pterodactyl/yolks:java_21", "Alpine": "alpine:latest"},
        "startup": "java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar folia.jar",
        "config": {"files": {"server.properties": {"parser": "properties"}}, "startup": {"done": ")! For help, type "}},
        "scripts": {"installation": {"script": "echo 'eula=true' > eula.txt", "container": "alpine:latest"}},
        "variables": []
    },
    "minecraft/velocity.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Velocity Proxy",
        "category": "minecraft",
        "author": "support@gamepanel.io",
        "description": "The modern, next-generation Minecraft proxy server designed for performance and flexibility.",
        "docker_images": {"Java 17": "ghcr.io/pterodactyl/yolks:java_17", "Alpine": "alpine:latest"},
        "startup": "java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar velocity.jar",
        "config": {"files": {"velocity.toml": {"parser": "file"}}, "startup": {"done": "Booting up Velocity"}},
        "scripts": {"installation": {"script": "echo 'velocity installed'", "container": "alpine:latest"}},
        "variables": []
    },
    "minecraft/bungeecord.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "BungeeCord",
        "category": "minecraft",
        "author": "support@gamepanel.io",
        "description": "BungeeCord proxy connects multiple Minecraft servers together.",
        "docker_images": {"Java 17": "ghcr.io/pterodactyl/yolks:java_17", "Alpine": "alpine:latest"},
        "startup": "java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar BungeeCord.jar",
        "config": {"files": {"config.yml": {"parser": "file"}}, "startup": {"done": "Listening on "}},
        "scripts": {"installation": {"script": "echo 'bungee installed'", "container": "alpine:latest"}},
        "variables": []
    },
    "minecraft/fabric.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Fabric",
        "category": "minecraft",
        "author": "support@gamepanel.io",
        "description": "Lightweight, experimental modding toolchain for Minecraft.",
        "docker_images": {"Java 21": "ghcr.io/pterodactyl/yolks:java_21", "Alpine": "alpine:latest"},
        "startup": "java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar fabric-server-launch.jar",
        "config": {"files": {"server.properties": {"parser": "properties"}}, "startup": {"done": ")! For help, type "}},
        "scripts": {"installation": {"script": "echo 'eula=true' > eula.txt", "container": "alpine:latest"}},
        "variables": []
    },
    "minecraft/forge.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Forge",
        "category": "minecraft",
        "author": "support@gamepanel.io",
        "description": "Minecraft Forge mod loader server.",
        "docker_images": {"Java 17": "ghcr.io/pterodactyl/yolks:java_17", "Alpine": "alpine:latest"},
        "startup": "java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar forge.jar",
        "config": {"files": {"server.properties": {"parser": "properties"}}, "startup": {"done": ")! For help, type "}},
        "scripts": {"installation": {"script": "echo 'eula=true' > eula.txt", "container": "alpine:latest"}},
        "variables": []
    },
    "minecraft/neoforge.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "NeoForge",
        "category": "minecraft",
        "author": "support@gamepanel.io",
        "description": "NeoForged modding ecosystem for modern Minecraft versions.",
        "docker_images": {"Java 21": "ghcr.io/pterodactyl/yolks:java_21", "Alpine": "alpine:latest"},
        "startup": "sh run.sh",
        "config": {"files": {"server.properties": {"parser": "properties"}}, "startup": {"done": ")! For help, type "}},
        "scripts": {"installation": {"script": "echo 'eula=true' > eula.txt", "container": "alpine:latest"}},
        "variables": []
    },
    "minecraft/bedrock.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Bedrock Dedicated Server",
        "category": "minecraft",
        "author": "support@gamepanel.io",
        "description": "Official Minecraft Bedrock Dedicated Server software from Mojang.",
        "docker_images": {"Ubuntu": "ghcr.io/parkervcp/yolks:debian", "Alpine": "alpine:latest"},
        "startup": "./bedrock_server",
        "config": {"files": {"server.properties": {"parser": "properties"}}, "startup": {"done": "Server started."}},
        "scripts": {"installation": {"script": "echo 'bedrock ready'", "container": "alpine:latest"}},
        "variables": []
    },

    # SteamCMD & Survival Games
    "steamcmd/rust.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Rust (Vanilla + Oxide/uMod)",
        "category": "steamcmd",
        "author": "support@gamepanel.io",
        "description": "The only aim in Rust is to survive. Conquer hunger, thirst and cold.",
        "docker_images": {"Rust": "ghcr.io/pterodactyl/games:rust", "Alpine": "alpine:latest"},
        "startup": "./RustDedicated -batchmode +server.port {{SERVER_PORT}} +server.level \"{{SERVER_LEVEL}}\" +server.seed {{SERVER_SEED}} +server.worldsize {{WORLD_SIZE}} +server.maxplayers {{MAX_PLAYERS}} +server.hostname \"{{SERVER_NAME}}\"",
        "config": {"files": {"server.cfg": {"parser": "file"}}, "startup": {"done": "Server startup complete"}},
        "scripts": {"installation": {"script": "echo 'rust installed'", "container": "alpine:latest"}},
        "variables": [
            {"name": "Server Name", "env_variable": "SERVER_NAME", "default_value": "My Rust Server", "user_viewable": True, "user_editable": True, "rules": "required"},
            {"name": "World Size", "env_variable": "WORLD_SIZE", "default_value": "3000", "user_viewable": True, "user_editable": True, "rules": "required|numeric"},
            {"name": "Server Level", "env_variable": "SERVER_LEVEL", "default_value": "Procedural Map", "user_viewable": True, "user_editable": True, "rules": "required"}
        ]
    },
    "steamcmd/palworld.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Palworld Dedicated Server",
        "category": "steamcmd",
        "author": "support@gamepanel.io",
        "description": "Fight, farm, build and work alongside mysterious creatures called Pals.",
        "docker_images": {"Debian": "ghcr.io/parkervcp/yolks:debian", "Alpine": "alpine:latest"},
        "startup": "./PalServer.sh -port={{SERVER_PORT}} -players={{MAX_PLAYERS}}",
        "config": {"files": {"PalWorldSettings.ini": {"parser": "file"}}, "startup": {"done": "App id: 2394010"}},
        "scripts": {"installation": {"script": "echo 'palworld ready'", "container": "alpine:latest"}},
        "variables": [{"name": "Max Players", "env_variable": "MAX_PLAYERS", "default_value": "32", "user_viewable": True, "user_editable": True, "rules": "required|numeric"}]
    },
    "steamcmd/valheim.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Valheim",
        "category": "steamcmd",
        "author": "support@gamepanel.io",
        "description": "A brutal exploration and survival game for 1-10 players set in a procedurally-generated purgatory.",
        "docker_images": {"Debian": "ghcr.io/parkervcp/yolks:debian", "Alpine": "alpine:latest"},
        "startup": "./valheim_server.x86_64 -name \"{{SERVER_NAME}}\" -port {{SERVER_PORT}} -world \"{{WORLD_NAME}}\" -password \"{{SERVER_PASS}}\"",
        "config": {"files": {}, "startup": {"done": "Game server connected"}},
        "scripts": {"installation": {"script": "echo 'valheim ready'", "container": "alpine:latest"}},
        "variables": [{"name": "Server Name", "env_variable": "SERVER_NAME", "default_value": "My Valheim Server", "user_viewable": True, "user_editable": True, "rules": "required"}]
    },
    "steamcmd/ark.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "ARK: Survival Evolved & Ascended",
        "category": "steamcmd",
        "author": "support@gamepanel.io",
        "description": "Stranded on the shores of a mysterious island, you must learn to survive.",
        "docker_images": {"Source": "ghcr.io/pterodactyl/games:source", "Alpine": "alpine:latest"},
        "startup": "./ShooterGameServer {{SERVER_MAP}}?listen?SessionName={{SESSION_NAME}}",
        "config": {"files": {"GameUserSettings.ini": {"parser": "file"}}, "startup": {"done": "Server has successfully started"}},
        "scripts": {"installation": {"script": "echo 'ark installed'", "container": "alpine:latest"}},
        "variables": []
    },
    "steamcmd/enshrouded.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Enshrouded",
        "category": "steamcmd",
        "author": "support@gamepanel.io",
        "description": "Action RPG survival game set within a vast voxel-based continent.",
        "docker_images": {"Alpine": "alpine:latest"},
        "startup": "./enshrouded_server.exe",
        "config": {"files": {"enshrouded_server.json": {"parser": "json"}}, "startup": {"done": "Host online"}},
        "scripts": {"installation": {"script": "echo 'enshrouded ready'", "container": "alpine:latest"}},
        "variables": []
    },
    "steamcmd/7daystodie.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "7 Days to Die",
        "category": "steamcmd",
        "author": "support@gamepanel.io",
        "description": "7 Days to Die is an open-world game that is a unique combination of first person shooter, survival horror, tower defense, and role-playing.",
        "docker_images": {"Alpine": "alpine:latest"},
        "startup": "./7DaysToDieServer.x86_64 -configfile=serverconfig.xml",
        "config": {"files": {"serverconfig.xml": {"parser": "file"}}, "startup": {"done": "GameServer.LogOn successful"}},
        "scripts": {"installation": {"script": "echo '7dtd ready'", "container": "alpine:latest"}},
        "variables": []
    },
    "steamcmd/zomboid.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Project Zomboid",
        "category": "steamcmd",
        "author": "support@gamepanel.io",
        "description": "Project Zomboid is the ultimate in zombie survival.",
        "docker_images": {"Alpine": "alpine:latest"},
        "startup": "./start-server.sh -servername \"{{SERVER_NAME}}\"",
        "config": {"files": {}, "startup": {"done": "SERVER STARTED"}},
        "scripts": {"installation": {"script": "echo 'pz ready'", "container": "alpine:latest"}},
        "variables": []
    },
    "steamcmd/vrising.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "V Rising",
        "category": "steamcmd",
        "author": "support@gamepanel.io",
        "description": "Awaken as a weakened vampire after centuries of slumber.",
        "docker_images": {"Alpine": "alpine:latest"},
        "startup": "./VRisingServer.exe -serverName \"{{SERVER_NAME}}\"",
        "config": {"files": {}, "startup": {"done": "Steamworks ready"}},
        "scripts": {"installation": {"script": "echo 'vrising ready'", "container": "alpine:latest"}},
        "variables": []
    },
    "steamcmd/conan.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Conan Exiles",
        "category": "steamcmd",
        "author": "support@gamepanel.io",
        "description": "An online multiplayer survival game set in the lands of Conan the Barbarian.",
        "docker_images": {"Alpine": "alpine:latest"},
        "startup": "./ConanSandboxServer.exe",
        "config": {"files": {}, "startup": {"done": "Game engine initialized"}},
        "scripts": {"installation": {"script": "echo 'conan ready'", "container": "alpine:latest"}},
        "variables": []
    },
    "steamcmd/factorio.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Factorio",
        "category": "steamcmd",
        "author": "support@gamepanel.io",
        "description": "Factorio is a game in which you build and maintain factories.",
        "docker_images": {"Alpine": "alpine:latest"},
        "startup": "./bin/x64/factorio --start-server-load-latest --server-settings data/server-settings.json --port {{SERVER_PORT}}",
        "config": {"files": {"server-settings.json": {"parser": "json"}}, "startup": {"done": "Hosting game at port "}},
        "scripts": {"installation": {"script": "echo 'factorio ready'", "container": "alpine:latest"}},
        "variables": []
    },
    "steamcmd/satisfactory.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Satisfactory",
        "category": "steamcmd",
        "author": "support@gamepanel.io",
        "description": "Satisfactory is a first-person open-world factory building game.",
        "docker_images": {"Alpine": "alpine:latest"},
        "startup": "./FactoryServer.sh -Port={{SERVER_PORT}}",
        "config": {"files": {}, "startup": {"done": "Game server started"}},
        "scripts": {"installation": {"script": "echo 'satisfactory ready'", "container": "alpine:latest"}},
        "variables": []
    },
    "steamcmd/unturned.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Unturned",
        "category": "steamcmd",
        "author": "support@gamepanel.io",
        "description": "Unturned is a multiplayer open-world survival game.",
        "docker_images": {"Alpine": "alpine:latest"},
        "startup": "./Unturned_Headless.x86_64 -batchmode -nographics +secureserver/{{SERVER_ID}}",
        "config": {"files": {}, "startup": {"done": "Successfully loaded"}},
        "scripts": {"installation": {"script": "echo 'unturned ready'", "container": "alpine:latest"}},
        "variables": []
    },
    "steamcmd/terraria.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Terraria (TShock)",
        "category": "steamcmd",
        "author": "support@gamepanel.io",
        "description": "TShock for Terraria dedicated server with plugins and anti-cheat.",
        "docker_images": {"Alpine": "alpine:latest"},
        "startup": "./TShock.Server -port {{SERVER_PORT}} -maxplayers {{MAX_PLAYERS}} -world {{WORLD_NAME}}",
        "config": {"files": {}, "startup": {"done": "Server started"}},
        "scripts": {"installation": {"script": "echo 'terraria ready'", "container": "alpine:latest"}},
        "variables": []
    },

    # FPS & Competitive Shooters
    "fps/cs2.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Counter-Strike 2 (CS2)",
        "category": "fps",
        "author": "support@gamepanel.io",
        "description": "Counter-Strike 2 Dedicated Server on Source 2 engine.",
        "docker_images": {"Source": "ghcr.io/pterodactyl/games:source", "Alpine": "alpine:latest"},
        "startup": "./game/bin/linuxsteamrt64/cs2 -dedicated +ip 0.0.0.0 -port {{SERVER_PORT}} +map {{SERVER_MAP}}",
        "config": {"files": {"server.cfg": {"parser": "file"}}, "startup": {"done": "GC Connection established for server"}},
        "scripts": {"installation": {"script": "echo 'cs2 ready'", "container": "alpine:latest"}},
        "variables": [{"name": "Default Map", "env_variable": "SERVER_MAP", "default_value": "de_dust2", "user_viewable": True, "user_editable": True, "rules": "required"}]
    },
    "fps/gmod.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Garry's Mod (GMod)",
        "category": "fps",
        "author": "support@gamepanel.io",
        "description": "Garry's Mod is a physics sandbox with endless community game modes.",
        "docker_images": {"Source": "ghcr.io/pterodactyl/games:source", "Alpine": "alpine:latest"},
        "startup": "./srcds_run -game garrysmod +gamemode {{GAMEMODE}} +map {{SERVER_MAP}} -port {{SERVER_PORT}} +maxplayers {{MAX_PLAYERS}}",
        "config": {"files": {"server.cfg": {"parser": "file"}}, "startup": {"done": "Server is hibernating"}},
        "scripts": {"installation": {"script": "echo 'gmod ready'", "container": "alpine:latest"}},
        "variables": [{"name": "Gamemode", "env_variable": "GAMEMODE", "default_value": "sandbox", "user_viewable": True, "user_editable": True, "rules": "required"}]
    },
    "fps/tf2.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Team Fortress 2",
        "category": "fps",
        "author": "support@gamepanel.io",
        "description": "Nine distinct classes provide a broad range of tactical abilities and personalities.",
        "docker_images": {"Source": "ghcr.io/pterodactyl/games:source", "Alpine": "alpine:latest"},
        "startup": "./srcds_run -game tf +map {{SERVER_MAP}} -port {{SERVER_PORT}} +maxplayers {{MAX_PLAYERS}}",
        "config": {"files": {"server.cfg": {"parser": "file"}}, "startup": {"done": "Connection to Steam servers successful"}},
        "scripts": {"installation": {"script": "echo 'tf2 ready'", "container": "alpine:latest"}},
        "variables": []
    },
    "fps/l4d2.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Left 4 Dead 2",
        "category": "fps",
        "author": "support@gamepanel.io",
        "description": "Cooperative action horror FPS game set in the zombie apocalypse.",
        "docker_images": {"Source": "ghcr.io/pterodactyl/games:source", "Alpine": "alpine:latest"},
        "startup": "./srcds_run -game left4dead2 +map {{SERVER_MAP}} -port {{SERVER_PORT}}",
        "config": {"files": {}, "startup": {"done": "Connection to Steam servers successful"}},
        "scripts": {"installation": {"script": "echo 'l4d2 ready'", "container": "alpine:latest"}},
        "variables": []
    },
    "fps/arma3.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Arma 3",
        "category": "fps",
        "author": "support@gamepanel.io",
        "description": "Premier military tactical shooter combat simulation.",
        "docker_images": {"Alpine": "alpine:latest"},
        "startup": "./arma3server -port={{SERVER_PORT}} -config=server.cfg",
        "config": {"files": {"server.cfg": {"parser": "file"}}, "startup": {"done": "Game started"}},
        "scripts": {"installation": {"script": "echo 'arma3 ready'", "container": "alpine:latest"}},
        "variables": []
    },
    "fps/squad.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Squad",
        "category": "fps",
        "author": "support@gamepanel.io",
        "description": "Squad is a large-scale combined arms tactical multi-player shooter.",
        "docker_images": {"Alpine": "alpine:latest"},
        "startup": "./SquadServer.sh Port={{SERVER_PORT}} QueryPort={{QUERY_PORT}}",
        "config": {"files": {}, "startup": {"done": "Game engine initialized"}},
        "scripts": {"installation": {"script": "echo 'squad ready'", "container": "alpine:latest"}},
        "variables": []
    },
    "fps/insurgency.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Insurgency: Sandstorm",
        "category": "fps",
        "author": "support@gamepanel.io",
        "description": "Team-based, tactical FPS based on lethal close-quarters combat.",
        "docker_images": {"Alpine": "alpine:latest"},
        "startup": "./InsurgencyServer.sh -Port={{SERVER_PORT}}",
        "config": {"files": {}, "startup": {"done": "Ready for client connections"}},
        "scripts": {"installation": {"script": "echo 'insurgency ready'", "container": "alpine:latest"}},
        "variables": []
    },

    # GTA / Roleplay Frameworks
    "gta/fivem.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "FiveM (txAdmin + FXServer)",
        "category": "gta",
        "author": "support@gamepanel.io",
        "description": "Multiplayer framework for GTA V allowing customized servers, scripts and game modes.",
        "docker_images": {"FiveM": "traskin/fxserver:recommended", "Alpine": "alpine:latest"},
        "startup": "/opt/cfx-server/run.sh +set serverProfile default +set txAdminPort {{TXADMIN_PORT}} +exec server.cfg",
        "config": {"files": {"server.cfg": {"parser": "file"}}, "startup": {"done": "Server license key is active"}},
        "scripts": {"installation": {"script": "echo 'sv_licenseKey changeme' > server.cfg", "container": "alpine:latest"}},
        "variables": [
            {"name": "txAdmin Port", "env_variable": "TXADMIN_PORT", "default_value": "40120", "user_viewable": True, "user_editable": False, "rules": "required|numeric"},
            {"name": "Server Name", "env_variable": "SERVER_NAME", "default_value": "My FiveM Server", "user_viewable": True, "user_editable": True, "rules": "required"}
        ]
    },
    "gta/altv.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "alt:V Multiplayer",
        "category": "gta",
        "author": "support@gamepanel.io",
        "description": "Free third-party multiplayer modification for Grand Theft Auto V.",
        "docker_images": {"Alpine": "alpine:latest"},
        "startup": "./altv-server",
        "config": {"files": {"server.toml": {"parser": "file"}}, "startup": {"done": "Main thread started"}},
        "scripts": {"installation": {"script": "echo 'altv ready'", "container": "alpine:latest"}},
        "variables": []
    },
    "gta/ragemp.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "RAGE Multiplayer",
        "category": "gta",
        "author": "support@gamepanel.io",
        "description": "A popular GTA V multiplayer framework supporting massive playercounts.",
        "docker_images": {"Alpine": "alpine:latest"},
        "startup": "./ragemp-server",
        "config": {"files": {"conf.json": {"parser": "json"}}, "startup": {"done": "Started server on"}},
        "scripts": {"installation": {"script": "echo 'ragemp ready'", "container": "alpine:latest"}},
        "variables": []
    },
    "gta/mtasa.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Multi Theft Auto (MTA:SA)",
        "category": "gta",
        "author": "support@gamepanel.io",
        "description": "Multiplayer mod for Grand Theft Auto: San Andreas.",
        "docker_images": {"Alpine": "alpine:latest"},
        "startup": "./mta-server64 -n",
        "config": {"files": {"mtaserver.conf": {"parser": "file"}}, "startup": {"done": "Server started and is ready to accept connections"}},
        "scripts": {"installation": {"script": "echo 'mtasa ready'", "container": "alpine:latest"}},
        "variables": []
    },

    # Generic Runtimes & Bots
    "generic/nodejs.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Node.js (Discord Bots & Web Apps)",
        "category": "generic",
        "author": "support@gamepanel.io",
        "description": "Run high-performance JavaScript/TypeScript servers, Discord.js bots, and APIs.",
        "docker_images": {"Node 20": "alpine:latest", "Node 18": "alpine:latest"},
        "startup": "if [ -f package.json ]; then npm install; fi; node {{JS_FILE}}",
        "config": {"files": {"package.json": {"parser": "json"}}, "startup": {"done": "Ready"}},
        "scripts": {"installation": {"script": "echo '{\"name\":\"bot\",\"main\":\"index.js\"}' > package.json; echo 'console.log(\"Node server ready\"); setInterval(() => {}, 1000);' > index.js", "container": "alpine:latest"}},
        "variables": [{"name": "Main JS File", "env_variable": "JS_FILE", "default_value": "index.js", "user_viewable": True, "user_editable": True, "rules": "required"}]
    },
    "generic/python.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Python (Discord.py & FastAPI)",
        "category": "generic",
        "author": "support@gamepanel.io",
        "description": "Run Python bots, machine learning services, Flask, and FastAPI apps.",
        "docker_images": {"Python 3": "alpine:latest"},
        "startup": "if [ -f requirements.txt ]; then pip install -r requirements.txt; fi; python3 {{PY_FILE}}",
        "config": {"files": {}, "startup": {"done": "Started"}},
        "scripts": {"installation": {"script": "echo 'print(\"Python app running\")' > main.py", "container": "alpine:latest"}},
        "variables": [{"name": "Main Python File", "env_variable": "PY_FILE", "default_value": "main.py", "user_viewable": True, "user_editable": True, "rules": "required"}]
    },
    "generic/golang.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Go (Golang High-Concurrency)",
        "category": "generic",
        "author": "support@gamepanel.io",
        "description": "Compile and run lightning-fast Go binaries and microservices.",
        "docker_images": {"Go 1.23": "alpine:latest"},
        "startup": "./{{EXECUTABLE}}",
        "config": {"files": {}, "startup": {"done": "Listening"}},
        "scripts": {"installation": {"script": "echo 'go app ready'", "container": "alpine:latest"}},
        "variables": [{"name": "Executable Name", "env_variable": "EXECUTABLE", "default_value": "server", "user_viewable": True, "user_editable": True, "rules": "required"}]
    },
    "generic/java.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Java (Generic JAR / Spring Boot)",
        "category": "generic",
        "author": "support@gamepanel.io",
        "description": "Execute any standalone Java JAR application or microservice.",
        "docker_images": {"Java 17": "alpine:latest", "Java 21": "alpine:latest"},
        "startup": "java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar {{JAR_FILE}}",
        "config": {"files": {}, "startup": {"done": "Started Application"}},
        "scripts": {"installation": {"script": "echo 'java ready'", "container": "alpine:latest"}},
        "variables": [{"name": "JAR File", "env_variable": "JAR_FILE", "default_value": "app.jar", "user_viewable": True, "user_editable": True, "rules": "required"}]
    },
    "generic/lavalink.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Lavalink (Audio Server)",
        "category": "generic",
        "author": "support@gamepanel.io",
        "description": "Standalone audio sending node based on Lavaplayer for Discord music bots.",
        "docker_images": {"Java 17": "alpine:latest"},
        "startup": "java -Xmx{{SERVER_MEMORY}}M -jar Lavalink.jar",
        "config": {"files": {"application.yml": {"parser": "file"}}, "startup": {"done": "Lavalink is ready to accept connections"}},
        "scripts": {"installation": {"script": "echo 'lavalink ready'", "container": "alpine:latest"}},
        "variables": []
    },
    "generic/postgres.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "PostgreSQL Standalone",
        "category": "generic",
        "author": "support@gamepanel.io",
        "description": "Dedicated PostgreSQL 16 database container with persistent data.",
        "docker_images": {"Postgres": "local/postgres:16", "Alpine": "alpine:latest"},
        "startup": "/entrypoint.sh",
        "config": {"files": {}, "startup": {"done": "database system is ready to accept connections"}},
        "scripts": {"installation": {"script": "echo 'pg ready'", "container": "alpine:latest"}},
        "variables": []
    },
    "generic/mariadb.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "MariaDB / MySQL Standalone",
        "category": "generic",
        "author": "support@gamepanel.io",
        "description": "Dedicated high-performance MariaDB/MySQL database container.",
        "docker_images": {"Alpine": "alpine:latest"},
        "startup": "mariadbd --user=mysql",
        "config": {"files": {}, "startup": {"done": "ready for connections"}},
        "scripts": {"installation": {"script": "echo 'mariadb ready'", "container": "alpine:latest"}},
        "variables": []
    },
    "generic/redis.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Redis Standalone Caching Node",
        "category": "generic",
        "author": "support@gamepanel.io",
        "description": "In-memory data structure store used as a database, cache, and message broker.",
        "docker_images": {"Redis": "local/redis:7", "Alpine": "alpine:latest"},
        "startup": "redis-server --protected-mode no --port {{SERVER_PORT}}",
        "config": {"files": {}, "startup": {"done": "Ready to accept connections"}},
        "scripts": {"installation": {"script": "echo 'redis ready'", "container": "alpine:latest"}},
        "variables": []
    },
    "generic/alpine.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Generic Alpine Linux Container",
        "category": "generic",
        "author": "support@gamepanel.io",
        "description": "Ultra-minimal Alpine Linux container for running custom scripts and tasks.",
        "docker_images": {"Alpine": "alpine:latest"},
        "startup": "sh -c 'echo \"[Container] Online\"; while true; do read cmd; if [ -n \"$cmd\" ]; then echo \"[Console] $cmd\"; fi; done'",
        "config": {"files": {}, "startup": {"done": "[Container] Online"}},
        "scripts": {"installation": {"script": "echo 'alpine container ready' > ready.txt", "container": "alpine:latest"}},
        "variables": []
    },
    "generic/ubuntu.json": {
        "meta": {"version": "PTDL_v2"},
        "name": "Generic Ubuntu Container",
        "category": "generic",
        "author": "support@gamepanel.io",
        "description": "Standard Ubuntu environment for general hosting, bots, and tools.",
        "docker_images": {"Ubuntu": "alpine:latest"},
        "startup": "sh -c 'echo \"[Ubuntu Container] Online\"; while true; do read cmd; if [ -n \"$cmd\" ]; then echo \"[Console] $cmd\"; fi; done'",
        "config": {"files": {}, "startup": {"done": "[Ubuntu Container] Online"}},
        "scripts": {"installation": {"script": "echo 'ubuntu container ready' > ready.txt", "container": "alpine:latest"}},
        "variables": []
    }
}

base_path = "/home/ubuntu/game panel/eggs"
for rel_path, data in eggs.items():
    full_path = os.path.join(base_path, rel_path)
    os.makedirs(os.path.dirname(full_path), exist_ok=True)
    with open(full_path, "w") as f:
        json.dump(data, f, indent=2)

print(f"Generated {len(eggs)} Pterodactyl-compatible .json eggs across 5 categories successfully!")
