<?php
/**
 * Componente de botón flotante para el carrito
 * Este componente muestra un botón flotante para acceder al carrito
 * Solo se muestra cuando hay productos en el carrito
 */

// Verificar si el usuario es cliente y tiene productos en el carrito
if ($es_cliente && $productos_carrito > 0):
?>
<!-- Botón flotante para el carrito -->
<div id="cart-float" class="fixed-action-btn cart-float" style="position: fixed; right: 30px; bottom: 30px; z-index: 997;">
    <a href="/tiendalex2/carrito.php" class="btn-floating btn-large" style="background-color: #2980b9; width: 60px; height: 60px; line-height: 60px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);">
        <i class="material-icons" style="font-size: 28px; line-height: 60px; display: block; width: 100%; text-align: center;">shopping_cart</i>
        <span class="badge new" data-badge-caption="" style="position: absolute; top: -5px; right: -5px; background-color: #e74c3c !important; font-size: 12px; font-weight: 600; padding: 3px 6px; min-width: 24px; height: 24px; line-height: 18px; border-radius: 12px; color: white; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);"><?php echo $productos_carrito; ?></span>
    </a>
</div>

<style>
/* Estilos para los botones flotantes */
/* Asegurar que los botones flotantes no se superpongan */
.fixed-action-btn {
    position: fixed !important;
    right: 30px !important;
    z-index: 997 !important;
}

/* Posición específica para el botón de carrito */
#cart-float {
    bottom: 30px !important;
}

/* Posición para el botón de chat */
#chat-float {
    bottom: 110px !important;
}

/* Posición para el botón de chat de administrador */
#admin-chat-float {
    bottom: 190px !important;
}

/* Posición para el botón de administración */
.admin-float {
    bottom: 270px !important;
}

/* Estilos comunes para los botones flotantes */
.fixed-action-btn .btn-floating {
    background-color: #2980b9 !important;
    transition: all 0.3s ease !important;
    width: 60px !important;
    height: 60px !important;
    line-height: 60px !important;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2) !important;
    position: relative !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.fixed-action-btn .btn-floating i {
    font-size: 28px !important;
    line-height: 1 !important;
    width: auto !important;
    height: auto !important;
}

.fixed-action-btn .btn-floating:hover {
    transform: scale(1.1) rotate(5deg) !important;
    background-color: #3498db !important;
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3) !important;
}

/* Estilos específicos para el badge del carrito */
#cart-float .badge {
    position: absolute !important;
    top: -5px !important;
    right: -5px !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    padding: 3px 6px !important;
    min-width: 24px !important;
    height: 24px !important;
    line-height: 18px !important;
    border-radius: 12px !important;
    background-color: #e74c3c !important;
    color: white !important;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2) !important;
    animation: pulse 2s infinite !important;
}

@keyframes pulse {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.7); }
    70% { transform: scale(1.1); box-shadow: 0 0 0 8px rgba(231, 76, 60, 0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(231, 76, 60, 0); }
}

/* Ajustes para móviles */
@media only screen and (max-width: 600px) {
    .fixed-action-btn {
        right: 20px !important;
    }
    
    #cart-float {
        bottom: 20px !important;
    }
    
    #chat-float {
        bottom: 100px !important;
    }
    
    #admin-chat-float {
        bottom: 180px !important;
    }
    
    .admin-float {
        bottom: 260px !important;
    }
    
    .fixed-action-btn .btn-floating {
        width: 56px !important;
        height: 56px !important;
        line-height: 56px !important;
    }
    
    .fixed-action-btn .btn-floating i {
        font-size: 26px !important;
    }
}
</style>
<?php endif; ?>
