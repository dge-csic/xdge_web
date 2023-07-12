CREATE TABLE entry (
    rowid               INTEGER, -- rowid auto
    xmlid  TEXT UNIQUE NOT NULL, -- entry/@xml:id
    lemma         TEXT NOT NULL, -- *Ἀhεριγος
    label         TEXT NOT NULL, -- *Ἀhεριγ<sup>u̯</sup>ος
    html          TEXT NOT NULL, -- displayable entry
    form          TEXT NOT NULL, -- lemma without ponctuation
    monoton       TEXT NOT NULL, -- lemma without diacritics
    latin         TEXT NOT NULL, -- latin script version of the lemma
    inverso       TEXT NOT NULL, -- reverse form
    PRIMARY KEY(rowid ASC)
);
CREATE INDEX entryLemma     ON entry (lemma ASC);
CREATE INDEX entryXmlid     ON entry (xmlid ASC);
CREATE INDEX entryForm      ON entry (form ASC);
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
    -- used in lemma column to have lemmas before
    rowid               INTEGER, -- rowid auto
    xmlid  TEXT UNIQUE NOT NULL, -- entry/@xml:id
    label         TEXT NOT NULL, -- *Ἀhεριγ<sup>u̯</sup>ος
    inverso       TEXT NOT NULL, -- reverse form
    PRIMARY KEY(rowid ASC)
);
CREATE INDEX inversoInverso ON inverso (inverso ASC, rowid ASC);
CREATE INDEX inversoXmlid   ON inverso (xmlid ASC);
