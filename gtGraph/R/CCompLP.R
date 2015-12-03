CCompLP <- function(data, inputs, outputs) {
    inputs <- convert.exprs(substitute(inputs))
    outputs <- convert.atts(substitute(outputs))
    gla <- GLA(graph::CCompLP)
    Aggregate(data, gla, inputs, outputs)
}
