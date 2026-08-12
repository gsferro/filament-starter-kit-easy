-- Roda apenas na criação do volume pgsql-data.
CREATE EXTENSION IF NOT EXISTS vector;    -- pgvector: embeddings/RAG (extensão futura)
CREATE EXTENSION IF NOT EXISTS unaccent;  -- busca sem acentuação (pt-BR)
CREATE EXTENSION IF NOT EXISTS pg_trgm;   -- busca fuzzy/trigram
