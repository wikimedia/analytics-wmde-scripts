CREATE TABLE IF NOT EXISTS wikidata_social
  (
     date       DATE NOT NULL,
     twitter    INT(6),
     facebook   INT(6),
     googleplus INT(6),
     identica   INT(6),
     newsletter INT(6),
     mail       INT(6),
     techmail   INT(6),
     irc        INT(6)
  );