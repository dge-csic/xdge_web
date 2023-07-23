CREATE TABLE entry (
    rowid               INTEGER, -- rowid auto
    name   TEXT UNIQUE NOT NULL, -- entry/@xml:id
    lemma         TEXT NOT NULL, -- *Ἀhεριγος
    label         BLOB NOT NULL, -- *Ἀhεριγ<sup>u̯</sup>ος
    html          BLOB NOT NULL, -- displayable entry
    toc                    BLOB, -- displayable entry
    prevnext      BLOB NOT NULL, -- displayable entry
    form          TEXT NOT NULL, -- lemma without ponctuation
    monoton       TEXT NOT NULL, -- lemma without diacritics
    latin         TEXT NOT NULL, -- latin script version of the lemma
    inverso       TEXT NOT NULL, -- reverse form
    PRIMARY KEY(rowid ASC)
);
CREATE INDEX entryLemma     ON entry (lemma ASC);
CREATE INDEX entryName      ON entry (name ASC);
CREATE INDEX entryForm      ON entry (form ASC);
-- find by monoton, rowid key should ensure to have the first 
CREATE INDEX entryMonoton   ON entry (monoton ASC, rowid ASC);
CREATE INDEX entryLatin     ON entry (latin DESC);
CREATE INDEX entryInverso   ON entry (inverso ASC, rowid ASC);

CREATE VIRTUAL TABLE entry_search USING FTS5 (
    -- table of searchable items
    text, -- searchable text, lowercase without tags
    tokenize = "unicode61 remove_diacritics 0"
);


CREATE TABLE inverso (
    -- table populated from entry in inverso order
    rowid               INTEGER, -- rowid auto
    name   TEXT UNIQUE NOT NULL, -- entry/@xml:id
    label         TEXT NOT NULL, -- *Ἀhεριγ<sup>u̯</sup>ος
    inverso       TEXT NOT NULL, -- reverse form
    PRIMARY KEY(rowid ASC)
);
CREATE INDEX inversoInverso ON inverso (inverso ASC, rowid ASC);
CREATE INDEX inversoName    ON inverso (name ASC);

CREATE TABLE bibl (
    rowid               INTEGER, -- rowid auto
    name   TEXT UNIQUE NOT NULL, -- {entry/@xml:id}_#
    label         BLOB NOT NULL, -- <span class="author">Hp.</span><cite>Epid.</cite><span class="biblScope">7.121</span>
    author                 TEXT, -- Hp.
    title                  TEXT, -- Epid.
    scope                  TEXT, -- 7.121 (for display in lists)
    entry               INTEGER, -- entry rowid
    entryname     TEXT NOT NULL, -- entry/@xml:id, to catch entry/rowid
    entrylabel    BLOB NOT NULL, -- to display entry
    PRIMARY KEY(rowid ASC)
);
CREATE INDEX biblSort ON bibl (author ASC, title ASC, entry ASC);


CREATE VIRTUAL TABLE search USING FTS5 (
    -- table of searchable items
    text                , -- searchable text, lowercase without tags
    type                , -- a token identifying content type
    name       UNINDEXED, -- generated anchor in entry
    html       UNINDEXED, -- displayable content
    branch     UNINDEXED, -- toc branch for component
    context    UNINDEXED, -- context in which insert hilited text
    entry      UNINDEXED, -- entry rowid
    entryname  UNINDEXED, -- entry/@xml:id
    entrylabel UNINDEXED, -- displayable lemma
    tokenize = "unicode61 remove_diacritics 0"
);
