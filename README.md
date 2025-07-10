# Control Visitas

Both the FrontEnd and BackEnd use environment variables for BigQuery access.
Use `BIGQUERY_ADMIN_DATASET` and `BIGQUERY_VISITAS_TABLE` in your `.env` files to
configure the dataset and table. The `FormularioController` now reads from these
configurations via `config('admin.bigquery.*')`, ensuring that submissions and
administration dashboards point to the same dataset and table.
