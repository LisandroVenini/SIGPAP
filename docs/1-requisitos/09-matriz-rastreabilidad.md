# Matriz de Rastreabilidad

La matriz de rastreabilidad vincula los objetivos del sistema con los requisitos y los casos de uso, permitiendo verificar que todo requisito está implementado y que todo caso de uso responde a un requisito.

## 1. Matriz Objetivo ↔ Requisito

| Requisito | OBJ-01 | OBJ-02 | OBJ-03 | OBJ-04 | OBJ-05 |
| :--- | :---: | :---: | :---: | :---: | :---: |
| IRQ-01 | X |  |  |  | X |
| IRQ-02 | X | X |  |  |  |
| IRQ-03 | X |  | X |  |  |
| IRQ-04 |  | X |  | X |  |
| IRQ-05 | X |  |  |  |  |
| RF-01 | X | X |  |  |  |
| RF-02 | X |  |  |  | X |
| RF-03 | X |  | X |  |  |
| RF-04 |  | X | X |  |  |
| RF-05 |  |  |  | X |  |
| RF-06 | X |  |  |  |  |
| RF-07 | X |  |  |  |  |
| NFR-01 | X |  |  |  |  |
| NFR-02 |  | X |  | X |  |
| NFR-03 | X |  |  |  |  |

## 2. Matriz Requisito ↔ Caso de Uso

| Requisito | Casos de Uso |
| :--- | :--- |
| IRQ-01 | CU-INV-01…CU-INV-08 |
| IRQ-02 | CU-PROD-06…CU-PROD-15, CU-PROD-21, CU-PROD-22 |
| IRQ-03 | CU-COM-01…CU-COM-20 |
| IRQ-04 | CU-PRED-01…CU-PRED-05, CU-REP-01 |
| IRQ-05 | CU-MANT-01…CU-MANT-11 |
| RF-01 | CU-PROD-01…CU-PROD-28, CU-MANT-01…CU-MANT-11 |
| RF-02 | CU-INV-01…CU-INV-08 |
| RF-03 | CU-COM-01…CU-COM-20 |
| RF-04 | CU-PRED-01…CU-PRED-05 |
| RF-05 | CU-PRED-04 |
| RF-06 | CU-MANT-01…CU-MANT-11 |
| RF-07 | CU-USR-01…CU-USR-07, CU-AUD-01, CU-REP-01 |
| NFR-01 | CU-USR-01…CU-USR-07 |
| NFR-02 | CU-PRED-01…CU-PRED-05 |
| NFR-03 | CU-PROD-13 |

## 3. Matriz Caso de Uso ↔ Módulo

| Módulo | Prefijo | Casos de Uso |
| :--- | :--- | :--- |
| Producción | CU-PROD | 01…28 |
| Mantenimiento (subdominio) | CU-MANT | 01…11 |
| Inventario | CU-INV | 01…08 |
| Compras | CU-COM | 01…20 |
| Análisis Predictivo | CU-PRED | 01…05 |
| Reportes | CU-REP | 01 |
| Auditoría | CU-AUD | 01 |
| Usuarios y Seguridad (soporte) | CU-USR | 01…07 |

## Verificación de completitud

- **Hacia adelante**: todos los requisitos (IRQ, RF, NFR) tienen al menos un caso de uso asociado. ✔
- **Hacia atrás**: los 81 casos de uso trazan a un requisito funcional o de información. ✔
- **Cobertura de objetivos**: los 5 objetivos (OBJ-01…05) están cubiertos por al menos un requisito. ✔
