-- This file declares a function similar to || concatenation operator (with the only difference beeing that if any
-- of the passed arguments is NULL, then an empty string is used, while || returns NULL in such case.
-- Then, a string concatenating aggregate is build upon this function.
-- @author p.piskorski

--DROP AGGREGATE IF EXISTS str_concat(text);
DROP FUNCTION IF EXISTS concat(text,text) CASCADE;

CREATE FUNCTION concat (text, text) RETURNS text AS $$
  BEGIN
    RETURN COALESCE($1,'') || COALESCE($2,'');
  END;
$$ LANGUAGE plpgsql;

CREATE AGGREGATE str_concat (
    sfunc = concat,
    basetype = text,
    stype = text,
    initcond = ''
);