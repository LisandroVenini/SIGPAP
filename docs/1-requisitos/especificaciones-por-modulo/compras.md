# Módulo Compras

## Operatoria

El módulo transforma la gestión de compras tradicional en una herramienta de gestión de riesgos, evaluando continuamente el desempeño real de la cadena de suministro para evitar paradas de planta. Integra dos circuitos de compra distintos según el contexto y apoya la decisión en un ranking dinámico de proveedores alimentado por datos reales de desempeño.

## Entidades del dominio

- **Proveedor**: entidad comercial externa. Datos: razón social, CUIT, email, teléfono. Posee un desempeño histórico (retrasos, faltantes, roturas) que alimenta el ranking.
- **Solicitud de Disponibilidad** (Flujo A): consulta directa a un único proveedor para confirmar disponibilidad, precio y fecha.
- **Solicitud de Cotización / RFQ** (Flujo B): pedido de cotización a múltiples proveedores con enlaces seguros y vencimiento.
- **Cotización**: respuesta de un proveedor con precio unitario, cantidad y fecha prometida.
- **Orden de Compra (OC)**: pedido firme emitido al proveedor ganador.
- **Ranking dinámico**: ordenamiento de proveedores por desempeño real, calculado automáticamente.

## Decisiones de negocio (validadas)

1. **Dos flujos de compra separados**: Flujo A (Solicitar Disponibilidad, proveedor único) y Flujo B (RFQ multi-proveedor) son casos de uso distintos.
2. **Ranking automático + consulta**: el ranking se calcula automáticamente (proceso interno + componente predictivo), pero existe un CU de consulta para verlo.
3. **Resolución DSS como CU del componente predictivo**: los escenarios del Flujo A se resuelven en el componente predictivo (actor ACT-05).

## Flujos de compra

### Flujo A — Compra directa con validación

1. El sistema detecta stock bajo y selecciona automáticamente al proveedor mejor rankeado.
2. El gerente aprueba la intención de compra.
3. El sistema envía al proveedor un correo con enlace seguro: "Confort y Muebles desea pedirte X. Confirmá disponibilidad, precio y fecha".
4. El proveedor carga los 3 datos desde el enlace.
5. El componente predictivo evalúa (resolución DSS):
   - **Ideal** (stock OK, precio dentro de umbral de tolerancia): genera la OC final.
   - **Sin stock**: descarta al proveedor y dispara al siguiente del ranking.
   - **Variación fuerte** (precio/tiempo rompe umbral): frena y alerta al gerente.

### Flujo B — Cotización de proveedores

1. Se detecta necesidad de compra.
2. El encargado genera la RFQ con los N mejores proveedores del ranking.
3. El sistema envía enlaces seguros con vencimiento (ej. 48 hs).
4. Cada proveedor carga precio unitario, cantidad y fecha prometida.
5. El sistema calcula puntuación ponderada: precio 40%, tiempo 30%, riesgo predictivo (IA) 30%.
6. El sistema presenta la tabla comparativa destacando la opción ganadora.
7. El gerente adjudica; el sistema emite la OC firme y expira los otros enlaces.

## Funcionalidades y pasos

### F1 — Gestión de proveedores (CU-COM-01)

1. Alta de proveedor: razón social, CUIT, email, teléfono.
2. Validación de CUIT único y email válido.
3. Modificación y baja lógica (conserva historial).
4. El desempeño (retrasos, faltantes, roturas) se acumula automáticamente y alimenta el ranking.

### F2 — Consultar ranking de proveedores (nuevo)

1. El Encargado de Compras/Gerente consulta el ranking dinámico.
2. El sistema muestra el ordenamiento y el detalle de desempeño de cada proveedor.

### F3 — Solicitar disponibilidad a proveedor (Flujo A — nuevo)

1. El sistema detecta stock bajo y propone el proveedor mejor rankeado.
2. El gerente aprueba la intención de compra.
3. El sistema envía el enlace seguro al proveedor seleccionado.

### F4 — Gestionar solicitud de cotización (Flujo B — CU-COM-02)

1. El usuario revisa el "Borrador de Solicitud" generado automáticamente, o crea una RFQ manual.
2. Define artículos, cantidades, vencimiento y proveedores.
3. El sistema envía los enlaces seguros a cada proveedor.

### F5 — Enviar cotización (CU-COM-04)

1. El proveedor accede por enlace seguro y carga precio, cantidad y fecha prometida.
2. El sistema valida el token y cierra el enlace tras el envío.

### F6 — Resolver disponibilidad de compra (componente predictivo — nuevo)

1. El componente predictivo recibe la respuesta del proveedor del Flujo A.
2. Evalúa contra el umbral de tolerancia configurado por el gerente.
3. Resuelve: genera OC (ideal), descarta y pasa al siguiente (sin stock), o alerta al gerente (variación fuerte).

### F7 — Adjudicar y emitir orden de compra (CU-COM-03)

1. El sistema presenta el informe multicriterio (precio 40% / tiempo 30% / riesgo 30%).
2. El usuario revisa y adjudica la oferta ganadora (o elige manualmente otra).
3. El sistema emite la OC oficial y notifica al proveedor.

### F8 — Confirmar orden de compra (CU-COM-05)

1. El proveedor recibe el enlace de confirmación y acepta o rechaza.
2. Aceptación: la OC pasa a "Confirmada" e inicia el reloj de tiempo de entrega real.
3. Rechazo: la OC pasa a "Rechazada" y dispara alerta urgente al Encargado de Compras.

### Procesos automáticos (no son casos de uso)

- **Cálculo del ranking**: evaluación continua del desempeño real (retrasos, faltantes, roturas) que reordena el ranking. Sin actor que lo dispare.
- **Detección de stock bajo**: umbrales de Inventario disparan la sugerencia de compra.

## Cruces con otros módulos

- **Inventario**: la detección de stock bajo proviene de los umbrales de Inventario; el ingreso de mercancía (F2 de Inventario) referencia la OC y actualiza la métrica de tiempo de entrega real del proveedor.
- **Análisis Predictivo**: el riesgo predictivo (30% del puntaje) y la resolución DSS provienen del componente predictivo.
- **Auditoría**: las decisiones de compra (adjudicación, resoluciones DSS) quedan registradas.

## Observaciones de la facultad (a aplicar en los casos de uso)

1. **Reestructuración de ABM**: "Gestionar Proveedores" (CU-COM-01) se desglosa en Alta, Baja, Modificación, Listado y Buscar.
2. **Nomenclatura consistente**: nombres descriptivos y uniformes.
