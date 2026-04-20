FROM php:8.2-cli

WORKDIR /app

# Servidor placeholder hasta que el servicio sea implementado
RUN echo '<?php echo json_encode(["service" => "posts-service", "status" => "pendiente de implementación", "owner" => "Malaga Ivan"]);' > index.php

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000"]
