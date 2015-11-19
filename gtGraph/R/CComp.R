CComp <- function(data, inputs, outputs) {
    inputs <- convert.exprs(substitute(inputs))
    outputs <- convert.atts(substitute(outputs))
    gla <- GLA(graph::ConnectedComponents)
    Aggregate(data, gla, inputs, outputs)
}
