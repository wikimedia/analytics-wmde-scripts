#Bulk Query of WDQS and write to TSV
source("config.R")
#TODO: create output path for analytics instance
#output_path = "/a/aggregate-datasets/wikidata/sparql/"
output_path = "/tmp/sparql/"
qlist <- read_file("rdfq.xml")

rdfq <- xmlParse(qlist)
queries <- xmlToDataFrame(nodes = getNodeSet(rdfq, "//rdfq:select", c(rdfq = "http://wikiba.se/rdfq#")))
prefixes <- xmlToDataFrame(nodes = getNodeSet(rdfq, "//rdfq:prefix", c(rdfq = "http://wikiba.se/rdfq#")))
comments <- xmlToDataFrame(nodes = getNodeSet(rdfq, "//rdfs:comment", c(rdfs = "http://www.w3.org/2000/01/rdf-schema#")))

get_sparql_result <- function(uri = wdqs_uri, prefix, query) {
  xml_result <- readLines(curl(paste0(uri, prefix, query)))
  doc = xmlParse(xml_result)
  result = xmlToDataFrame(nodes = getNodeSet(doc, "//sq:literal", c(sq = "http://www.w3.org/2005/sparql-results#")))
  return(result)
}

write_tsv <- function(result, filename){
  date = Sys.Date()
  file_uri <- paste0(output_path, filename)
  out = data.frame(date, result)
  write.table(out, file=file_uri, append = TRUE, sep = "\t", row.names = FALSE, col.names = FALSE)
}

bulk_sparql_query <- function(esc_queries) {
  for(q in esc_queries) {
    result <- get_sparql_result(wdqs_uri, pfx, q)
    tsv_filename <- paste0("spql", match(q, esc_queries), ".tsv")
    write_tsv(result, tsv_filename)
  }
}

esc_queries <- lapply(queries$text, curl_escape)
pfx <- paste(prefixes$text, collapse="")
bulk_sparql_query (esc_queries)
