-- Queue each TADA batch for mailing exactly once.
-- DomesticTada / International_tada rows are inserted one per employee (and edits add
-- more rows), so the triggers must only enqueue batches that are not already queued.

-- Hard guarantee against duplicate queue rows (e.g. two requests inserting the same batch at once).
-- Remove any existing duplicates first, keeping the oldest row per batch.
;WITH d AS (
    SELECT ROW_NUMBER() OVER (PARTITION BY Batch_id, SourceTable ORDER BY Queue_id) AS rn
    FROM dbo.BatchMailQueue
)
DELETE FROM d WHERE rn > 1;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'UX_BatchMailQueue_Batch_Source' AND object_id = OBJECT_ID('dbo.BatchMailQueue'))
    CREATE UNIQUE INDEX UX_BatchMailQueue_Batch_Source ON dbo.BatchMailQueue (Batch_id, SourceTable);
GO

CREATE OR ALTER TRIGGER dbo.trg_DomesticTada_Insert
ON dbo.DomesticTada
AFTER INSERT
AS
BEGIN
    SET NOCOUNT ON;

    INSERT INTO dbo.BatchMailQueue (Batch_id, SourceTable)
    SELECT DISTINCT i.domestic_Batch_id, 'Domestic'
    FROM inserted i
    WHERE i.domestic_Batch_id IS NOT NULL
      AND NOT EXISTS (
          SELECT 1 FROM dbo.BatchMailQueue q
          WHERE q.Batch_id = i.domestic_Batch_id AND q.SourceTable = 'Domestic'
      );
END;
GO

CREATE OR ALTER TRIGGER dbo.trg_InternationalTada_Insert
ON dbo.International_tada
AFTER INSERT
AS
BEGIN
    SET NOCOUNT ON;

    INSERT INTO dbo.BatchMailQueue (Batch_id, SourceTable)
    SELECT DISTINCT i.Batch_id, 'International'
    FROM inserted i
    WHERE i.Batch_id IS NOT NULL
      AND NOT EXISTS (
          SELECT 1 FROM dbo.BatchMailQueue q
          WHERE q.Batch_id = i.Batch_id AND q.SourceTable = 'International'
      );
END;
GO
