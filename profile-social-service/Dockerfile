FROM php:8.2-cli

WORKDIR /app

# Servidor placeholder hasta que el servicio sea implementado
RUN echo '<?php echo json_encode(["service" => "profile-social-service", "status" => "pendiente de implementación", "owner" => "Chino Angel"]);' > index.php

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000"]
