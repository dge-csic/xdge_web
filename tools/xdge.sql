CREATE TABLE entry (
    rowid               INTEGER, -- rowid auto
    xmlid  TEXT UNIQUE NOT NULL, -- entry/@xml:id
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
CREATE INDEX entryXmlid     ON entry (xmlid ASC);
CREATE INDEX entryForm      ON entry (form ASC);
-- find by monoton, rowid key should ensure to have the first 
CREATE INDEX entryMonoton   ON entry (monoton ASC, rowid ASC);
CREATE INDEX entryLatin     ON entry (latin DESC);
CREATE INDEX entryInverso   ON entry (inverso ASC, rowid ASC);

CREATE VIRTUAL TABLE search USING FTS3 (
    -- table of searchable items
    rowid                  INTEGER, -- rowid auto
    entry     INT,  -- entry rowid
    xmlid        TEXT, -- entry/@xml:id
    lemma     TEXT, -- *Ἀhεριγος
    label     TEXT, -- *Ἀhεριγ<sup>u̯</sup>ος
    anchor    TEXT, -- relative anchor in entry
    type      TEXT, -- content type
    text      TEXT, -- exact text
    monoton   TEXT, -- desaccentuated text
    PRIMARY KEY(rowid ASC)
);

CREATE TABLE inverso (
    -- table populated from entry in inverso order
    rowid               INTEGER, -- rowid auto
    xmlid  TEXT UNIQUE NOT NULL, -- entry/@xml:id
    label         TEXT NOT NULL, -- *Ἀhεριγ<sup>u̯</sup>ος
    inverso       TEXT NOT NULL, -- reverse form
    PRIMARY KEY(rowid ASC)
);
CREATE INDEX inversoInverso ON inverso (inverso ASC, rowid ASC);
CREATE INDEX inversoXmlid   ON inverso (xmlid ASC);

CREATE TABLE bibl (
    rowid               INTEGER, -- rowid auto
    label         BLOB NOT NULL, -- <span class="bibl" id="bibl820251"><span class="author">Hp.</span><cite>Epid.</cite><span class="biblScope">7.121</span></span>
    author                 TEXT, -- Hp.
    title                  TEXT, -- Epid.
    scope                  TEXT, -- 7.121 (for displayx in lists)
    entry               INTEGER, -- entry rowid
    entrycode     NOT NULL TEXT, -- entry/@xml:id, to catch entry/rowid
    PRIMARY KEY(rowid ASC)
);